<?php

namespace App\Http\Controllers;

use App\Models\QadItem;
use App\Models\User;
use App\Models\WoPartOrder;
use App\Models\WoPartOrderLine;
use App\Models\WorkOrder;
use App\Services\ApprovalService;
use App\Services\Qad\QadItemService;
use App\Services\Qad\QadRequisitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    private function warehouseOrdersQuery()
    {
        $user = Auth::user();

        return WoPartOrder::query()->whereHas('workOrder', function ($q) use ($user) {
            $q->where('target_department_id', $user->department_id);
        });
    }

    public function index()
    {
        $user = Auth::user();

        $pendingOrders = $this->warehouseOrdersQuery()
            ->with(['workOrder.requester', 'workOrder.spareParts', 'requestedBy', 'lines'])
            ->where('status', 'pending_warehouse')
            ->latest()
            ->get();

        $activeOrders = $this->warehouseOrdersQuery()
            ->with(['workOrder.requester', 'workOrder.spareParts', 'requestedBy', 'handledBy', 'lines'])
            ->where('status', 'pr_created')
            ->latest()
            ->get();

        $inProcessOrders = $pendingOrders->concat($activeOrders)
            ->sortByDesc('created_at')
            ->values();

        $receivedOrders = $this->warehouseOrdersQuery()
            ->with(['workOrder.requester', 'handledBy'])
            ->where('status', 'received')
            ->where('received_at', '>=', now()->subDays(30))
            ->latest('received_at')
            ->get();

        // Stats
        $stats = [
            'pending' => $pendingOrders->count(),
            'in_progress' => $activeOrders->count(),
            'received_30d' => $receivedOrders->count(),
            'overdue' => $activeOrders->filter(fn ($o) => $o->isOverdue())->count(),
        ];

        // Average procurement days (last 90 days)
        $avgDays = $this->warehouseOrdersQuery()
            ->where('status', 'received')
            ->whereNotNull('pr_date')
            ->whereNotNull('received_at')
            ->where('received_at', '>=', now()->subDays(90))
            ->get()
            ->map(fn ($o) => $o->procurement_days)
            ->filter()
            ->average();

        $stats['avg_procurement_days'] = $avgDays ? round($avgDays, 1) : null;

        // Monthly procurement trend (last 6 months)
        $monthlyTrend = $this->warehouseOrdersQuery()
            ->where('status', 'received')
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
        $allReceived = $this->warehouseOrdersQuery()
            ->where('status', 'received')->whereNotNull('pr_date')->whereNotNull('received_at')->get();
        $onTime = $allReceived->filter(fn ($o) => $o->procurement_days !== null && $o->procurement_days <= 30)->count();
        $stats['compliance_rate'] = $allReceived->count() > 0
            ? round(($onTime / $allReceived->count()) * 100, 1)
            : null;

        return view('warehouse.index', compact(
            'pendingOrders', 'activeOrders', 'receivedOrders', 'inProcessOrders', 'stats', 'monthlyTrend'
        ));
    }

    // Full historical list of every part order (not just pending/active/last-30-days)
    public function history(Request $request)
    {
        $orders = $this->warehouseOrdersQuery()
            ->with(['workOrder', 'requestedBy', 'handledBy'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = $request->q;
                $query->where(fn ($q) => $q
                    ->where('pr_number', 'like', "%{$term}%")
                    ->orWhereHas('workOrder', fn ($q2) => $q2->where('wo_number', 'like', "%{$term}%")));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('warehouse.history', compact('orders'));
    }

    // Warehouse — or the WO's own assigned staffer, acting as their own warehouse — sends the PR to QAD
    public function createPr(Request $request, WorkOrder $workOrder, QadRequisitionService $qad)
    {
        $user = Auth::user();
        abort_unless($this->canManageOrder($user, $workOrder), 403);
        abort_unless($workOrder->status === 'pending_parts', 422, 'WO tidak dalam status menunggu parts.');

        $request->validate([
            'need_date' => 'required|date',
            'warehouse_note' => 'nullable|string|max:500',
        ]);

        // Update the existing part order
        $order = WoPartOrder::where('wo_id', $workOrder->id)
            ->where('status', 'pending_warehouse')
            ->firstOrFail();

        abort_unless($order->lines()->exists(), 422, 'Tambahkan minimal 1 item sparepart sebelum membuat PR.');

        $order->update([
            'handled_by' => $user->id,
            'need_date' => $request->need_date,
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

    // Warehouse — or the WO's own assigned staffer — records a real goods
    // receipt against the QAD PO, per line, and only advances the WO once
    // every line's cumulative received qty (per QAD, not our own guess)
    // has reached what was ordered. Supports partial receiving: whatever
    // isn't fully received yet just stays queued for next time.
    public function receive(Request $request, WoPartOrder $partOrder, ApprovalService $service, QadRequisitionService $qad)
    {
        $actor = Auth::user();
        abort_unless($this->canManageOrder($actor, $partOrder->workOrder), 403);
        abort_unless($partOrder->status === 'pr_created', 422);
        abort_unless($partOrder->qad_po_no, 422, 'PO QAD belum tersedia. Cek status PO dulu sebelum menerima barang.');
        abort_unless($actor->canReceiveInQad(), 403, 'Kamu belum punya login QAD sendiri — hubungi admin untuk didaftarkan sebagai penerima barang.');

        $partOrder->load('lines');

        $request->validate([
            'note' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.line_id' => 'required|integer|exists:wo_part_order_lines,id',
            'items.*.qty_received' => 'required|numeric|min:0',
        ]);

        // Line numbers are positional (1-based) — must match the order
        // lines were sent to QAD in when the PR was created (WoPartOrder::lines()
        // is explicitly ordered by id for exactly this reason).
        $lineNoByLineId = [];
        foreach ($partOrder->lines as $i => $line) {
            $lineNoByLineId[$line->id] = $i + 1;
        }

        $before = $qad->getReceivedQtyByLine($partOrder->pr_number);

        $qadLines = [];
        foreach ($request->items as $item) {
            $qty = (float) $item['qty_received'];
            if ($qty <= 0 || ! isset($lineNoByLineId[$item['line_id']])) {
                continue;
            }
            $qadLines[] = [
                'line' => $lineNoByLineId[$item['line_id']],
                'qty' => $qty,
            ];
        }

        if (empty($qadLines)) {
            return back()->withInput()->with('error', 'Isi minimal 1 qty yang diterima.');
        }

        $result = $qad->receivePurchaseOrder($partOrder, $qadLines, $actor);

        if (! $result['success']) {
            return back()->withInput()->with('error', "Gagal catat penerimaan ke QAD: {$result['message']}");
        }

        // QAD has been known to reply success without the quantity actually
        // posting — re-read and only trust the receipt if it really moved.
        $after = $qad->getReceivedQtyByLine($partOrder->pr_number);

        foreach ($qadLines as $qadLine) {
            $expected = ($before[$qadLine['line']] ?? 0.0) + $qadLine['qty'];
            $actual = $after[$qadLine['line']] ?? 0.0;

            if ($actual < $expected) {
                return back()->withInput()->with('error',
                    "QAD merespons sukses tapi qty belum ter-update di data QAD (baris {$qadLine['line']}: baru {$actual} dari {$expected} yang diharapkan). ".
                    'Belum dianggap diterima — silakan cek/coba lagi beberapa saat lagi.');
            }
        }

        $fullyReceived = true;
        foreach ($lineNoByLineId as $lineId => $lineNo) {
            $line = $partOrder->lines->firstWhere('id', $lineId);
            if (($after[$lineNo] ?? 0.0) < (float) $line->quantity) {
                $fullyReceived = false;
                break;
            }
        }

        $partOrder->update([
            'warehouse_note' => $partOrder->warehouse_note.($request->note ? "\n[Receiving] ".$request->note : ''),
            'qad_response' => $result['message'],
            ...($fullyReceived ? ['status' => 'received', 'received_at' => now()] : []),
        ]);

        if ($fullyReceived) {
            $service->onPartsReceived($partOrder->workOrder, $actor);

            return back()->with('success', 'Semua barang sudah diterima dan tercatat di QAD. WO akan dilanjutkan.');
        }

        return back()->with('success', 'Penerimaan sebagian tercatat di QAD. Masih ada item yang belum lengkap — lanjutkan kapan saja.');
    }

    // Show detail of a part order / WO procurement
    public function showOrder(Request $request, WoPartOrder $partOrder, QadItemService $items, QadRequisitionService $qad)
    {
        $partOrder->load(['workOrder.requester', 'lines.qadItem', 'requestedBy', 'handledBy']);
        $actor = Auth::user();
        abort_unless($this->canManageOrder($actor, $partOrder->workOrder), 403);

        $results = $items->browse($request->q, 30);
        $itemMasterCount = QadItem::active()->withoutExcludedProdLines()->count();
        $canReceive = $actor->canReceiveInQad();

        // Live remaining-to-receive qty per line, straight from QAD — only
        // meaningful once a real PO exists and it's not fully received yet.
        $receivedByLineId = [];
        $poLines = [];
        if ($partOrder->qad_po_no && $partOrder->status === 'pr_created') {
            $receivedByLine = $qad->getReceivedQtyByLine($partOrder->pr_number);
            foreach ($partOrder->lines as $i => $line) {
                $receivedByLineId[$line->id] = $receivedByLine[$i + 1] ?? 0.0;
            }
        }
        if ($partOrder->qad_po_no) {
            $poLines = $qad->findPurchaseOrderLines($partOrder->qad_po_no, $partOrder->pr_date);
        }

        return view('warehouse.order', compact('partOrder', 'results', 'itemMasterCount', 'receivedByLineId', 'canReceive', 'poLines'));
    }

    // On-demand check of whether QAD has approved & converted this PR to a PO yet
    public function checkPo(WoPartOrder $partOrder, QadRequisitionService $qad)
    {
        abort_unless($this->canManageOrder(Auth::user(), $partOrder->workOrder), 403);
        abort_unless($partOrder->pr_number, 422, 'Order ini belum punya No. PR.');

        $result = $qad->findPurchaseOrder($partOrder->pr_number);

        if (! $result) {
            return back()->with('success', 'Belum ada respons dari QAD — PR masih menunggu approval.');
        }

        $partOrder->update([
            'qad_approval_status' => $result['approval_status'],
            'qad_po_no' => $result['po_no'] ?? $partOrder->qad_po_no,
        ]);

        if ($result['approval_status'] !== '2') {
            return back()->with('success', 'Belum disetujui — PR masih menunggu approval di QAD.');
        }

        return back()->with('success', $result['po_no']
            ? "PR sudah disetujui — PO {$result['po_no']}."
            : 'PR sudah disetujui — menunggu No. PO diterbitkan QAD.');
    }

    // Add a chosen QAD item (or a manually-typed one) as a PR line
    public function addLine(Request $request, WoPartOrder $partOrder)
    {
        $user = Auth::user();
        abort_unless($this->canManageOrder($user, $partOrder->workOrder), 403);
        abort_unless($partOrder->status === 'pending_warehouse', 422, 'Order sudah diproses.');

        $request->validate([
            'mode' => 'required|in:catalog,custom',
            'qad_item_id' => 'required_if:mode,catalog|nullable|integer|exists:qad_items,id',
            'description' => 'required_if:mode,custom|nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'uom' => 'required|string|max:20',
        ]);

        $line = [
            'quantity' => $request->quantity,
            'uom' => $request->uom,
            'added_by' => $user->id,
        ];

        if ($request->mode === 'catalog') {
            $item = QadItem::findOrFail($request->qad_item_id);
            $line += [
                'qad_item_id' => $item->id,
                'part_code' => $item->qad_code,
                'description' => $item->description ?: $item->qad_code,
                'is_custom' => false,
            ];
        } else {
            $name = trim((string) $request->description);
            $line += [
                'qad_item_id' => null,
                'part_code' => $name,
                'description' => $name,
                'is_custom' => true,
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
     * Warehouse MTC / MTC Section Head manage Maintenance orders;
     * GA Section Head manages GA orders (same PR flow).
     */
    private function canManageOrder(User $user, WorkOrder $workOrder): bool
    {
        return $user->managesWarehouseFor($workOrder);
    }
}
