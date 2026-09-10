<?php

namespace App\Http\Controllers;

use App\Models\QadItem;
use App\Models\User;
use App\Models\WoPartOrder;
use App\Models\WoPartOrderLine;
use App\Models\WorkOrder;
use App\Services\ApprovalService;
use App\Services\Qad\QadRequisitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $pendingOrders = WoPartOrder::with(['workOrder.requester', 'workOrder.spareParts', 'requestedBy'])
            ->where('status', 'pending_warehouse')
            ->latest()
            ->get();

        $activeOrders = WoPartOrder::with(['workOrder.requester', 'workOrder.spareParts', 'requestedBy', 'handledBy'])
            ->where('status', 'pr_created')
            ->latest()
            ->get();

        $receivedOrders = WoPartOrder::with(['workOrder.requester', 'handledBy'])
            ->where('status', 'received')
            ->where('received_at', '>=', now()->subDays(30))
            ->latest('received_at')
            ->get();

        // Stats
        $stats = [
            'pending'      => $pendingOrders->count(),
            'in_progress'  => $activeOrders->count(),
            'received_30d' => $receivedOrders->count(),
            'overdue'      => $activeOrders->filter(fn ($o) => $o->isOverdue())->count(),
        ];

        // Average procurement days (last 90 days)
        $avgDays = WoPartOrder::where('status', 'received')
            ->whereNotNull('pr_date')
            ->whereNotNull('received_at')
            ->where('received_at', '>=', now()->subDays(90))
            ->get()
            ->map(fn ($o) => $o->procurement_days)
            ->filter()
            ->average();

        $stats['avg_procurement_days'] = $avgDays ? round($avgDays, 1) : null;

        // Monthly procurement trend (last 6 months)
        $monthlyTrend = WoPartOrder::where('status', 'received')
            ->where('received_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw("DATE_FORMAT(received_at, '%Y-%m') as month"),
                DB::raw('COUNT(*) as total'),
                DB::raw('AVG(DATEDIFF(received_at, pr_date)) as avg_days')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Compliance rate: received within 30 days
        $allReceived = WoPartOrder::where('status', 'received')->whereNotNull('pr_date')->whereNotNull('received_at')->get();
        $onTime      = $allReceived->filter(fn ($o) => $o->procurement_days !== null && $o->procurement_days <= 30)->count();
        $stats['compliance_rate'] = $allReceived->count() > 0
            ? round(($onTime / $allReceived->count()) * 100, 1)
            : null;

        return view('warehouse.index', compact(
            'pendingOrders', 'activeOrders', 'receivedOrders', 'stats', 'monthlyTrend'
        ));
    }

    // Warehouse — or the WO's own assigned staffer, acting as their own warehouse — sends the PR to QAD
    public function createPr(Request $request, WorkOrder $workOrder, QadRequisitionService $qad)
    {
        $user = Auth::user();
        abort_unless($this->canManageOrder($user, $workOrder), 403);
        abort_unless($workOrder->status === 'pending_parts', 422, 'WO tidak dalam status menunggu parts.');

        $request->validate([
            'warehouse_note' => 'nullable|string|max:500',
        ]);

        // Update the existing part order
        $order = WoPartOrder::where('wo_id', $workOrder->id)
            ->where('status', 'pending_warehouse')
            ->firstOrFail();

        abort_unless($order->lines()->exists(), 422, 'Tambahkan minimal 1 item sparepart sebelum membuat PR.');

        $order->update([
            'handled_by' => $user->id,
            'warehouse_note' => $request->warehouse_note,
        ]);

        $result = $qad->createRequisition($order);

        if (! $result['success']) {
            $order->update(['qad_response' => $result['message']]);

            return back()->with('error', "Gagal membuat PR: {$result['message']}");
        }

        $order->update([
            'pr_number' => $result['qad_req_no'],
            'status' => 'pr_created',
            'pr_date' => now()->toDateString(),
            'expected_arrival' => now()->addDays(30)->toDateString(),
            'qad_response' => $result['message'],
        ]);

        $workOrder->update(['status' => 'parts_ordered']);
        $workOrder->addHistory($user->id, 'parts_ordered',
            "PR dibuat: {$result['qad_req_no']}. Estimasi tiba: ".now()->addDays(30)->format('d M Y'));

        return back()->with('success', "PR {$result['qad_req_no']} berhasil dibuat. Estimasi tiba ".now()->addDays(30)->format('d M Y').'.');
    }

    // Warehouse — or the WO's own assigned staffer — receives the parts
    public function receive(Request $request, WoPartOrder $partOrder, ApprovalService $service)
    {
        $actor = Auth::user();
        abort_unless($this->canManageOrder($actor, $partOrder->workOrder), 403);
        abort_unless($partOrder->status === 'pr_created', 422);

        $request->validate(['note' => 'nullable|string|max:500']);

        $partOrder->update([
            'status'         => 'received',
            'received_at'    => now(),
            'warehouse_note' => $partOrder->warehouse_note . ($request->note ? "\n[Receiving] " . $request->note : ''),
        ]);

        $service->onPartsReceived($partOrder->workOrder, $actor);

        return back()->with('success', 'Parts diterima. Unit Head akan diberitahu untuk melanjutkan WO.');
    }

    // Show detail of a part order / WO procurement
    public function showOrder(Request $request, WoPartOrder $partOrder)
    {
        $partOrder->load(['workOrder.requester', 'lines.qadItem', 'requestedBy', 'handledBy']);
        abort_unless($this->canManageOrder(Auth::user(), $partOrder->workOrder), 403);

        $results = $request->filled('q')
            ? QadItem::active()->search($request->q)->orderBy('description')->limit(30)->get()
            : collect();

        return view('warehouse.order', compact('partOrder', 'results'));
    }

    // Add a chosen QAD item (or a manually-typed one) as a PR line
    public function addLine(Request $request, WoPartOrder $partOrder)
    {
        $user = Auth::user();
        abort_unless($this->canManageOrder($user, $partOrder->workOrder), 403);
        abort_unless($partOrder->status === 'pending_warehouse', 422, 'Order sudah diproses.');

        $request->validate([
            'mode'        => 'required|in:catalog,custom',
            'qad_item_id' => 'required_if:mode,catalog|nullable|integer|exists:qad_items,id',
            'description' => 'required_if:mode,custom|nullable|string|max:255',
            'quantity'    => 'required|numeric|min:0.01',
            'uom'         => 'required|string|max:20',
            'needed_date' => 'nullable|date',
        ]);

        $line = [
            'quantity'    => $request->quantity,
            'uom'         => $request->uom,
            'needed_date' => $request->needed_date,
            'added_by'    => $user->id,
        ];

        if ($request->mode === 'catalog') {
            $item = QadItem::findOrFail($request->qad_item_id);
            $line += [
                'qad_item_id' => $item->id,
                'part_code'   => $item->qad_code,
                'description' => $item->description ?: $item->qad_code,
                'is_custom'   => false,
            ];
        } else {
            $line += [
                'qad_item_id' => null,
                'part_code'   => null,
                'description' => $request->description,
                'is_custom'   => true,
            ];
        }

        $partOrder->lines()->create($line);

        return back()->with('success', 'Item ditambahkan.');
    }

    // Remove a previously-added PR line
    public function removeLine(WoPartOrder $partOrder, WoPartOrderLine $line)
    {
        $user = Auth::user();
        abort_unless($this->canManageOrder($user, $partOrder->workOrder), 403);
        abort_unless($partOrder->status === 'pending_warehouse', 422, 'Order sudah diproses.');
        abort_unless($line->wo_part_order_id === $partOrder->id, 404);

        $line->delete();

        return back()->with('success', 'Item dihapus.');
    }

    /**
     * Dedicated warehouse staff can manage any order; a WO's own assigned
     * staffer can manage their own order too (self-service PR flow, e.g.
     * GA's material_check step).
     */
    private function canManageOrder(User $user, WorkOrder $workOrder): bool
    {
        return $user->isWarehouseMtc()
            || $user->isSectionHead()
            || $user->id === $workOrder->assigned_member_id;
    }
}
