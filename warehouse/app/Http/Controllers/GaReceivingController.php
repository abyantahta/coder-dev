<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\Transaction;
use App\Services\BudgetService;
use App\Services\QadSoapService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GaReceivingController extends Controller
{
    public function __construct(
        private StockService $stockService,
        private QadSoapService $qadSoap,
        private BudgetService $budgetService,
    ) {}

    public function index()
    {
        $purchaseRequests = PurchaseRequest::with('creator')
            ->where('source', 'ga_aggregation')
            ->whereIn('status', ['director_confirmed', 'partially_received'])
            ->latest()->get();

        $history = PurchaseRequest::with('creator', 'receivedBy', 'details.procurementItem.uom')
            ->where('source', 'ga_aggregation')
            ->whereIn('status', ['distributing', 'completed'])
            ->latest('received_at')->paginate(15);

        // Budget vs actual bulan berjalan per baris detail (key = detail id).
        $budgetInfo = [];
        $cache = [];
        foreach ($history as $pr) {
            if (!$pr->department_id) continue;
            foreach ($pr->details as $d) {
                $key = $pr->department_id . '-' . $d->procurement_item_id;
                if (!isset($cache[$key])) {
                    $budget   = $this->budgetService->effectiveBudget($pr->department_id, $d->procurement_item_id);
                    $consumed = $this->budgetService->consumed($pr->department_id, $d->procurement_item_id);
                    $cache[$key] = ['budget' => $budget, 'consumed' => $consumed, 'remaining' => $budget - $consumed];
                }
                $budgetInfo[$d->id] = $cache[$key];
            }
        }

        return view('procurement.ga.receiving.index', compact('purchaseRequests', 'history', 'budgetInfo'));
    }

    public function create(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->source !== 'ga_aggregation' || !in_array($purchaseRequest->status, ['director_confirmed', 'partially_received'])) {
            return back()->with('error', 'PR ini belum siap untuk diterima.');
        }

        $purchaseRequest->load(['details.procurementItem.uom', 'details.sources.department', 'department']);

        $defaultNotes = 'Penerimaan barang untuk Departemen ' . ($purchaseRequest->department->name ?? '-');

        // Qty yang sudah beneran tercatat diterima di QAD (bisa dari receipt
        // sebagian sebelumnya) — dipakai buat tampilkan sisa yang masih perlu
        // diterima per baris, bukan qty_needed penuh lagi.
        $receivedByDetailId = [];
        if ($purchaseRequest->qad_req_no) {
            $receivedByLine = $this->qadSoap->getReceivedQtyByLine($purchaseRequest->qad_req_no);
            foreach ($purchaseRequest->details as $i => $detail) {
                $receivedByDetailId[$detail->id] = $receivedByLine[$i + 1] ?? 0.0;
            }
        }

        return view('procurement.ga.receiving.create', compact('purchaseRequest', 'defaultNotes', 'receivedByDetailId'));
    }

    public function store(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->source !== 'ga_aggregation' || !in_array($purchaseRequest->status, ['director_confirmed', 'partially_received'])) {
            return back()->with('error', 'PR ini belum siap untuk diterima.');
        }

        $request->validate([
            'qad_po_no'              => 'required|string|max:30',
            'qad_receipt_no'         => 'nullable|string|max:50',
            'notes'                  => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.detail_id'      => 'required|exists:purchase_request_details,id',
            'items.*.qty_received'   => 'required|numeric|min:0',
        ]);

        $purchaseRequest->load('details.procurementItem.uom', 'details.sources');

        // Baris PO di QAD urutannya sama dengan urutan detail requisition asal
        // (dipakai juga saat SDI_CreatePR) — mapping detail_id -> nomor line.
        $lineNoByDetailId = [];
        foreach ($purchaseRequest->details as $i => $detail) {
            $lineNoByDetailId[$detail->id] = $i + 1;
        }

        $detailsById = $purchaseRequest->details->keyBy('id');

        $qadLines = [];
        foreach ($request->items as $line) {
            $qty = (float) $line['qty_received'];
            if ($qty <= 0 || !isset($lineNoByDetailId[$line['detail_id']])) continue;
            $qadLines[] = [
                'line' => $lineNoByDetailId[$line['detail_id']],
                'qty'  => $qty,
                'um'   => $detailsById[$line['detail_id']]->procurementItem->uom->code ?? null,
            ];
        }

        // Ambil qty yang SUDAH tercatat diterima di QAD sebelum kirim — dipakai
        // sebagai baseline validasi (support penerimaan bertahap/partial), karena
        // getReceivedQtyByLine selalu balikin angka KUMULATIF, bukan per-transaksi.
        $beforeByLine = $purchaseRequest->qad_req_no
            ? $this->qadSoap->getReceivedQtyByLine($purchaseRequest->qad_req_no)
            : [];

        $qadResult = $this->qadSoap->receivePurchaseOrder($request->qad_po_no, $qadLines);
        if (!$qadResult['success']) {
            return back()->withInput()->with('error', "Gagal catat penerimaan ke QAD: {$qadResult['message']}");
        }

        // QAD pernah terbukti balas result=success padahal Quantity Received
        // di PO-nya sendiri tetap 0 (silent no-op) — jadi response "sukses"
        // SAJA tidak cukup dipercaya. Validasi ulang qty yang benar-benar
        // tercatat di QAD (kumulatif sebelum + yang baru dikirim) sebelum PR
        // ini dianggap diterima & stok dikreditkan.
        $afterByLine = [];
        if ($purchaseRequest->qad_req_no) {
            $afterByLine = $this->qadSoap->getReceivedQtyByLine($purchaseRequest->qad_req_no);
            foreach ($qadLines as $line) {
                $expected = ($beforeByLine[$line['line']] ?? 0.0) + $line['qty'];
                $actual   = $afterByLine[$line['line']] ?? 0.0;
                if ($actual < $expected) {
                    return back()->withInput()->with('error',
                        "QAD merespons sukses tapi Quantity Received belum ter-update di data QAD (baris {$line['line']}: baru {$actual} dari {$expected} yang diharapkan). "
                        . 'Belum dianggap diterima — silakan cek/coba lagi beberapa saat lagi, atau hubungi admin QAD kalau berulang.');
                }
            }
        }

        // PR baru dianggap SELESAI (distributing) kalau SEMUA baris sudah
        // tercapai qty_needed-nya secara kumulatif di QAD. Kalau masih ada yang
        // kurang, statusnya "partially_received" — tetap di antrian, GA bisa
        // lanjut terima sisanya kapan saja.
        $fullyReceived = true;
        foreach ($purchaseRequest->details as $i => $detail) {
            $cumulative = $afterByLine[$i + 1] ?? null;
            // Kalau QAD (WSA) belum dikonfigurasi / tidak bisa dicek, jangan
            // asumsikan penuh — anggap belum lengkap supaya aman (tetap partial).
            if ($cumulative === null || $cumulative < $detail->qty_needed) {
                $fullyReceived = false;
                break;
            }
        }
        $newStatus = $fullyReceived ? 'distributing' : 'partially_received';

        $gaDeptId = Department::where('code', 'GA')->value('id');

        DB::transaction(function () use ($request, $purchaseRequest, $gaDeptId, $qadResult, $newStatus) {
            $trx = Transaction::create([
                'trans_no'       => Transaction::generateTransNo('goods_in'),
                'trans_type'     => 'goods_in',
                'user_id'        => auth()->id(),
                'department_id'  => $gaDeptId,
                'status'         => 'completed',
                'approved_by'    => auth()->id(),
                'approved_at'    => now(),
                'notes'          => "Penerimaan PR {$purchaseRequest->pr_no}. " . ($request->notes ?? ''),
                'qad_receipt_no' => $request->qad_receipt_no,
                'trans_date'     => today(),
            ]);

            foreach ($request->items as $line) {
                $qtyReceived = (float) $line['qty_received'];
                if ($qtyReceived <= 0) continue;

                $detail = $purchaseRequest->details->firstWhere('id', $line['detail_id']);
                if (!$detail) continue;

                $item = $this->stockService->resolveOrCreateWarehouseItem($detail->procurementItem);

                $trx->details()->create([
                    'item_id'       => $item->id,
                    'qty_requested' => $detail->qty_needed,
                    'qty_approved'  => $qtyReceived,
                ]);

                // Alokasikan ke masing-masing dept secara proporsional terhadap qty yang diminta
                $ratio = $detail->qty_needed > 0 ? $qtyReceived / $detail->qty_needed : 0;
                foreach ($detail->sources as $source) {
                    $deptQty = round($source->qty * $ratio, 2);
                    if ($deptQty <= 0) continue;

                    $this->stockService->creditDepartmentStock($item, $source->department_id, $deptQty);
                }
            }

            $purchaseRequest->update([
                'status'           => $newStatus,
                'qad_po_no'        => $request->qad_po_no,
                'received_by'      => auth()->id(),
                'received_at'      => now(),
                'distributed_at'   => now(),
                'qad_sync_message' => $qadResult['message'],
            ]);
        });

        $message = $newStatus === 'partially_received'
            ? "Penerimaan sebagian PR {$purchaseRequest->pr_no} dicatat ke QAD, barang yang diterima sudah dialokasikan. Masih ada sisa qty yang belum diterima — PR ini tetap ada di antrian untuk dilanjutkan."
            : "Penerimaan PR {$purchaseRequest->pr_no} dicatat ke QAD dan barang sudah dialokasikan ke departemen.";

        return redirect()->route('ga.receiving.index')->with('success', $message);
    }
}
