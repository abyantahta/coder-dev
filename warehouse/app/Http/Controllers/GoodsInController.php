<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Item;
use App\Models\Transaction;
use App\Services\QXtendService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsInController extends Controller
{
    public function __construct(
        private QXtendService $qxtend,
        private StockService $stockService,
    ) {}

    public function index()
    {
        $transactions = Transaction::with(['user', 'department', 'details.item'])
            ->where('trans_type', 'goods_in')
            ->latest()
            ->paginate(15);
        return view('transactions.goods-in.index', compact('transactions'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        return view('transactions.goods-in.create', compact('departments'));
    }

    public function getQADReceipts(Request $request)
    {
        $from = $request->get('from', today()->startOfMonth()->format('Y-m-d'));
        $to   = $request->get('to', today()->format('Y-m-d'));

        $receipts = $this->qxtend->getReceipts($from, $to);

        if (empty($receipts)) {
            // Return mock data if QAD not connected
            $receipts = [
                ['receipt_no' => 'RCV-2026-001', 'po_no' => 'PO-001', 'vendor' => 'PT. Supplier A', 'receipt_date' => today()->format('Y-m-d'), 'total_lines' => 3],
                ['receipt_no' => 'RCV-2026-002', 'po_no' => 'PO-002', 'vendor' => 'PT. Supplier B', 'receipt_date' => today()->subDay()->format('Y-m-d'), 'total_lines' => 2],
            ];
        }

        return response()->json(['success' => true, 'data' => $receipts]);
    }

    public function getReceiptDetail(Request $request)
    {
        $receiptNo = $request->get('receipt_no');
        $lines     = $this->qxtend->getReceiptDetail($receiptNo);

        if (empty($lines)) {
            // Mock data
            $lines = [
                ['item_code' => 'ITM-001', 'description' => 'Baut M8x30mm', 'qty_received' => 100, 'unit' => 'PCS'],
                ['item_code' => 'ITM-003', 'description' => 'Bearing 6205',  'qty_received' => 20,  'unit' => 'PCS'],
            ];
        }

        return response()->json(['success' => true, 'data' => $lines]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'qad_receipt_no' => 'required|string',
            'department_id'  => 'required|exists:departments,id',
            'notes'          => 'nullable|string|max:500',
            'items'          => 'required|array|min:1',
            'items.*.item_code'    => 'required|string',
            'items.*.qty_received' => 'required|numeric|min:0.01',
        ]);

        $transaction = DB::transaction(function () use ($request) {
            $trx = Transaction::create([
                'trans_no'       => Transaction::generateTransNo('goods_in'),
                'trans_type'     => 'goods_in',
                'user_id'        => auth()->id(),
                'department_id'  => $request->department_id,
                'status'         => 'completed',
                'approved_by'    => auth()->id(),
                'approved_at'    => now(),
                'notes'          => $request->notes,
                'qad_receipt_no' => $request->qad_receipt_no,
                'trans_date'     => today(),
            ]);

            foreach ($request->items as $line) {
                $item = Item::where('item_code', $line['item_code'])->first();
                if (!$item) continue;

                $trx->details()->create([
                    'item_id'       => $item->id,
                    'qty_requested' => $line['qty_received'],
                    'qty_approved'  => $line['qty_received'],
                ]);

                $this->stockService->creditDepartmentStock($item, $request->department_id, (float) $line['qty_received']);
            }

            return $trx;
        });

        return redirect()->route('transactions.goods-in.index')
            ->with('success', "Penerimaan barang {$transaction->trans_no} berhasil dicatat.");
    }
}
