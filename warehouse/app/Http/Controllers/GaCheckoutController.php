<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentStock;
use App\Models\Item;
use App\Models\ProcurementRequestDetail;
use App\Models\PurchaseRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GaCheckoutController extends Controller
{
    public function __construct(private StockService $stockService) {}

    public function selectDepartment()
    {
        $departments = Department::where('is_active', true)
            ->withCount(['departmentStocks as items_available' => fn($q) => $q->where('qty_held', '>', 0)])
            ->get();

        return view('procurement.ga.checkout.select-department', compact('departments'));
    }

    public function scan(Request $request)
    {
        $request->validate(['department_id' => 'required|exists:departments,id']);
        $department = Department::findOrFail($request->department_id);

        return view('procurement.ga.checkout.scan', compact('department'));
    }

    public function lookup(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
            'identifier'    => 'required|string',
        ]);

        $employee = User::where('department_id', $request->department_id)
            ->where(fn($q) => $q->where('qr_code', $request->identifier)->orWhere('email', $request->identifier))
            ->where('is_active', true)
            ->first();

        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan di departemen ini.'], 404);
        }

        return response()->json([
            'success'  => true,
            'redirect' => route('ga.checkout.show', $employee->id),
        ]);
    }

    public function show(User $employee)
    {
        $lines = ProcurementRequestDetail::whereHas('request', function ($q) use ($employee) {
                $q->where('user_id', $employee->id)->where('status', 'aggregated');
            })
            ->with(['item', 'request'])
            ->get()
            ->map(function (ProcurementRequestDetail $detail) {
                $item = Item::where('item_code', $detail->item->item_code)->first();
                $held = $item
                    ? DepartmentStock::where('item_id', $item->id)->where('department_id', $detail->request->department_id)->value('qty_held')
                    : 0;

                return (object) [
                    'detail'    => $detail,
                    'item'      => $item,
                    'remaining' => $detail->remainingQty(),
                    'available' => min($detail->remainingQty(), (float) ($held ?? 0)),
                ];
            })
            ->filter(fn($row) => $row->remaining > 0)
            ->values();

        return view('procurement.ga.checkout.show', compact('employee', 'lines'));
    }

    public function store(Request $request, User $employee)
    {
        $request->validate([
            'items'                          => 'required|array|min:1',
            'items.*.procurement_request_detail_id' => 'required|exists:procurement_request_details,id',
            'items.*.qty'                    => 'required|numeric|min:0',
        ]);

        // Pre-validasi semua baris dulu sebelum ada perubahan apapun ke DB
        $lines = [];
        foreach ($request->items as $line) {
            $qty = (float) $line['qty'];
            if ($qty <= 0) continue;

            $detail = ProcurementRequestDetail::with('item', 'request')->findOrFail($line['procurement_request_detail_id']);

            if ($detail->request->user_id !== $employee->id) {
                return back()->with('error', 'Baris permintaan tidak sesuai dengan karyawan ini.');
            }

            $remaining = $detail->remainingQty();
            if ($qty > $remaining) {
                return back()->with('error', "Qty {$detail->item->name} melebihi sisa permintaan ({$remaining}).");
            }

            $item = Item::where('item_code', $detail->item->item_code)->first();
            if (!$item) {
                return back()->with('error', "Item {$detail->item->name} belum pernah diterima di gudang.");
            }

            $deptStock = DepartmentStock::where('item_id', $item->id)
                ->where('department_id', $detail->request->department_id)
                ->first();

            if (!$deptStock || $qty > $deptStock->qty_held) {
                return back()->with('error', "Stok departemen untuk {$detail->item->name} tidak mencukupi.");
            }

            $lines[] = compact('qty', 'detail', 'item', 'deptStock');
        }

        if (empty($lines)) {
            return back()->with('error', 'Tidak ada qty yang diisi.');
        }

        $touchedDetailIds = [];

        try {
            DB::transaction(function () use ($lines, $employee, &$touchedDetailIds) {
                $trx = Transaction::create([
                    'trans_no'      => Transaction::generateTransNo('ga_checkout'),
                    'trans_type'    => 'ga_checkout',
                    'user_id'       => auth()->id(),
                    'requester_id'  => $employee->id,
                    'department_id' => $employee->department_id,
                    'status'        => 'completed',
                    'approved_by'   => auth()->id(),
                    'approved_at'   => now(),
                    'trans_date'    => today(),
                ]);

                foreach ($lines as ['qty' => $qty, 'detail' => $detail, 'item' => $item]) {
                    $trx->details()->create([
                        'item_id'                       => $item->id,
                        'procurement_request_detail_id' => $detail->id,
                        'qty_requested'                  => $detail->qty,
                        'qty_approved'                   => $qty,
                    ]);

                    $this->stockService->debitDepartmentStock($item, $detail->request->department_id, $qty);

                    $touchedDetailIds[] = $detail->id;
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->maybeCompletePurchaseRequests($touchedDetailIds);

        return redirect()->route('ga.checkout.show', $employee->id)
            ->with('success', "Barang berhasil diserahkan ke {$employee->name}.");
    }

    private function maybeCompletePurchaseRequests(array $procurementRequestDetailIds): void
    {
        if (empty($procurementRequestDetailIds)) return;

        $prIds = ProcurementRequestDetail::whereIn('id', $procurementRequestDetailIds)
            ->with('request')
            ->get()
            ->pluck('request.aggregated_into_pr_id')
            ->filter()
            ->unique();

        foreach ($prIds as $prId) {
            $pr = PurchaseRequest::with('details.procurementItem', 'details.sources')->find($prId);
            if (!$pr || $pr->status !== 'distributing') continue;

            $stillHeld = false;
            foreach ($pr->details as $detail) {
                $item = Item::where('item_code', $detail->procurementItem->item_code)->first();
                if (!$item) continue;
                $deptIds = $detail->sources->pluck('department_id')->unique();
                $held = DepartmentStock::where('item_id', $item->id)->whereIn('department_id', $deptIds)->sum('qty_held');
                if ($held > 0) { $stillHeld = true; break; }
            }

            if (!$stillHeld) {
                $pr->update(['status' => 'completed']);
            }
        }
    }
}
