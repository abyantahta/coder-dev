<?php

namespace App\Http\Controllers;

use App\Models\ProcurementRequest;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use App\Models\PurchaseRequestDetailSource;
use App\Models\User;
use App\Notifications\DirectorBudgetSummaryNotification;
use App\Services\BudgetService;
use App\Services\QadSoapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class GaReviewController extends Controller
{
    public function __construct(private QadSoapService $qadSoap, private BudgetService $budgetService) {}

    public function index()
    {
        $requests = ProcurementRequest::with(['user', 'department', 'details.item.uom', 'attachments'])
            ->where('status', 'pending_ga')
            ->latest()->get();

        $byDepartment = $requests->groupBy(fn($r) => $r->department?->name ?? 'Tanpa Departemen');

        // Summary: akumulasi qty per item lintas departemen
        $summary = $requests
            ->flatMap(fn($r) => $r->details)
            ->groupBy('item_id')
            ->map(function ($details) {
                $first = $details->first();
                return (object) [
                    'item'  => $first->item,
                    'qty'   => $details->sum('qty'),
                    'count' => $details->count(),
                ];
            })->values();

        // Riwayat: request yang sudah diagregasi GA (detail expand di bawah,
        // sama gayanya dengan halaman Approval Section).
        $history = ProcurementRequest::with(['user', 'department', 'details.item.uom', 'attachments', 'aggregatedPr'])
            ->where('status', 'aggregated')
            ->latest('aggregated_at')->limit(50)->get();

        $budgetInfo = $this->buildBudgetInfo($requests->concat($history));

        return view('procurement.ga.review.index', compact('requests', 'byDepartment', 'summary', 'history', 'budgetInfo'));
    }

    /**
     * Budget vs actual bulan berjalan per baris detail (key = detail id),
     * dipakai buat ditampilkan di dalam expand-row item pending & riwayat.
     */
    private function buildBudgetInfo($requests): array
    {
        $info  = [];
        $cache = [];

        foreach ($requests as $req) {
            if (!$req->department_id) continue;
            foreach ($req->details as $d) {
                $key = $req->department_id . '-' . $d->item_id;
                if (!isset($cache[$key])) {
                    $budget   = $this->budgetService->effectiveBudget($req->department_id, $d->item_id);
                    $consumed = $this->budgetService->consumed($req->department_id, $d->item_id);
                    $cache[$key] = ['budget' => $budget, 'consumed' => $consumed, 'remaining' => $budget - $consumed];
                }
                $info[$d->id] = $cache[$key];
            }
        }

        return $info;
    }

    /**
     * Riwayat PR hasil agregasi GA (satu PR = satu departemen sejak redesain ini).
     */
    public function requestsIndex()
    {
        $purchaseRequests = PurchaseRequest::with('creator', 'department', 'details.procurementItem.uom')
            ->where('source', 'ga_aggregation')
            ->latest()->paginate(15);

        // Budget vs actual bulan berjalan per baris detail (key = detail id).
        $budgetInfo = [];
        $cache = [];
        foreach ($purchaseRequests as $pr) {
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

        return view('procurement.ga.requests.index', compact('purchaseRequests', 'budgetInfo'));
    }

    public function requestsShow(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->source !== 'ga_aggregation') {
            abort(404);
        }

        $purchaseRequest->load(['creator', 'department', 'directorBy', 'details.procurementItem.uom', 'details.sources.department', 'details.sources.procurementRequestDetail.request']);

        return view('procurement.ga.requests.show', compact('purchaseRequest'));
    }

    public function approve(Request $request, ProcurementRequest $procurementRequest)
    {
        return $this->aggregate($request, collect([$procurementRequest]));
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'request_ids'   => 'required|array|min:1',
            'request_ids.*' => 'exists:procurement_requests,id',
        ]);

        $requests = ProcurementRequest::whereIn('id', $request->request_ids)
            ->where('status', 'pending_ga')
            ->with('details')
            ->get();

        if ($requests->isEmpty()) {
            return back()->with('error', 'Tidak ada permintaan valid yang dipilih.');
        }

        return $this->aggregate($request, $requests);
    }

    /**
     * Bikin 1 PurchaseRequest PER DEPARTEMEN dari request yang dicentang, lalu
     * langsung kirim tiap PR ke QAD via SOAP (bukan menunggu approval Direktur
     * lagi — Direktur konfirmasi belakangan dari web ini).
     */
    private function aggregate(Request $request, $requests)
    {
        $user = auth()->user();

        foreach ($requests as $pr) {
            if (!$pr->canBeApprovedBy($user)) {
                return back()->with('error', "Permintaan {$pr->req_no} tidak dalam status yang bisa direview GA.");
            }
        }

        $purchaseRequests = DB::transaction(function () use ($requests, $user, $request) {
            $created = collect();

            foreach ($requests->groupBy('department_id') as $departmentId => $deptRequests) {
                $pr = PurchaseRequest::create([
                    'pr_no'         => PurchaseRequest::generatePRNo(),
                    'source'        => 'ga_aggregation',
                    'department_id' => $departmentId,
                    'status'        => 'draft',
                    'notes'         => $request->notes,
                    'created_by'    => $user->id,
                ]);

                $allDetails = $deptRequests->flatMap(fn($r) => $r->details->map(fn($d) => [$r, $d]));

                foreach ($allDetails->groupBy(fn($pair) => $pair[1]->item_id) as $itemId => $pairs) {
                    $prDetail = PurchaseRequestDetail::create([
                        'pr_id'               => $pr->id,
                        'procurement_item_id' => $itemId,
                        'qty_needed'          => collect($pairs)->sum(fn($pair) => $pair[1]->qty),
                    ]);

                    foreach ($pairs as [$sourceRequest, $sourceDetail]) {
                        PurchaseRequestDetailSource::create([
                            'pr_detail_id'                   => $prDetail->id,
                            'procurement_request_detail_id'  => $sourceDetail->id,
                            'department_id'                  => $sourceRequest->department_id,
                            'qty'                             => $sourceDetail->qty,
                        ]);
                    }
                }

                foreach ($deptRequests as $sourceRequest) {
                    $sourceRequest->update([
                        'status'                => 'aggregated',
                        'ga_by'                 => $user->id,
                        'ga_at'                 => now(),
                        'ga_notes'              => $request->notes,
                        'aggregated_into_pr_id' => $pr->id,
                        'aggregated_at'         => now(),
                    ]);
                }

                $created->push($pr);
            }

            return new \Illuminate\Database\Eloquent\Collection($created->all());
        });

        // Kirim ke QAD di LUAR transaksi DB (HTTP call lambat, jangan tahan lock).
        foreach ($purchaseRequests as $pr) {
            $this->sendToQad($pr);
        }

        $this->notifyDirectors($purchaseRequests);

        $sentCount = $purchaseRequests->where('status', 'sent_to_qad')->count();
        $failCount = $purchaseRequests->where('status', 'send_failed')->count();

        $msg = "{$purchaseRequests->count()} PR dibuat ({$purchaseRequests->pluck('department.name')->implode(', ')}).";
        $msg .= $failCount ? " {$sentCount} berhasil terkirim ke QAD, {$failCount} GAGAL — cek Riwayat PR untuk retry." : " Semua berhasil terkirim ke QAD.";

        return redirect()->route('ga.review.index')->with($failCount ? 'error' : 'success', $msg);
    }

    private function sendToQad(PurchaseRequest $pr): void
    {
        $result = $this->qadSoap->createRequisition($pr);

        if (!empty($result['success'])) {
            $pr->update([
                'status'           => 'sent_to_qad',
                'sent_to_qad_at'   => now(),
                'qad_req_no'       => $result['qad_pr_no'],
                'qad_sync_message' => $result['message'],
            ]);
        } else {
            $pr->update([
                'status'           => 'send_failed',
                'qad_sync_message' => $result['message'] ?? 'Gagal mengirim ke QAD',
            ]);
        }
    }

    public function retrySend(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->source !== 'ga_aggregation' || $purchaseRequest->status !== 'send_failed') {
            return back()->with('error', 'Hanya PR berstatus gagal kirim yang bisa dicoba ulang.');
        }

        $purchaseRequest->load('details.procurementItem.uom');
        $this->sendToQad($purchaseRequest);

        return back()->with('success', 'Pengiriman ke QAD dicoba ulang — cek status terbaru.');
    }

    private function notifyDirectors($purchaseRequests): void
    {
        $purchaseRequests->load('department', 'details.procurementItem.uom');
        $directors = User::where('role', 'director')->where('is_active', true)->get();

        if ($directors->isNotEmpty()) {
            Notification::send($directors, new DirectorBudgetSummaryNotification($purchaseRequests));
        }
    }

    /**
     * Form revisi qty untuk PR yang ditolak Direktur — GA ubah qty, lalu kirim
     * ulang pakai NOMOR REQUISITION YANG SAMA (bukan bikin PR/nomor QAD baru).
     */
    public function reviseForm(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->source !== 'ga_aggregation' || $purchaseRequest->status !== 'director_denied') {
            return back()->with('error', 'PR ini tidak dalam status yang bisa direvisi.');
        }

        $purchaseRequest->load('details.procurementItem.uom');

        return view('procurement.ga.requests.revise', compact('purchaseRequest'));
    }

    public function reviseSubmit(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->source !== 'ga_aggregation' || $purchaseRequest->status !== 'director_denied') {
            return back()->with('error', 'PR ini tidak dalam status yang bisa direvisi.');
        }

        $request->validate([
            'qty'      => 'required|array',
            'qty.*'    => 'required|numeric|min:0.01',
            'notes'    => 'nullable|string|max:500',
        ]);

        foreach ($request->qty as $detailId => $qty) {
            $purchaseRequest->details()->where('id', $detailId)->update(['qty_needed' => $qty]);
        }

        $purchaseRequest->update([
            'status'           => 'sent_to_qad',
            'notes'            => $request->notes ?: $purchaseRequest->notes,
            'director_by'      => null,
            'director_at'      => null,
            'director_notes'   => null,
            'reject_reason'    => null,
            'qad_sync_message' => null,
        ]);

        $purchaseRequest->load('details.procurementItem.uom');
        $this->sendToQad($purchaseRequest);

        if ($purchaseRequest->fresh()->status === 'send_failed') {
            return redirect()->route('ga.requests.show', $purchaseRequest->id)
                ->with('error', 'Revisi tersimpan tapi gagal dikirim ulang ke QAD — coba kirim ulang dari halaman detail.');
        }

        $this->notifyDirectors(new \Illuminate\Database\Eloquent\Collection([$purchaseRequest->fresh()]));

        return redirect()->route('ga.requests.show', $purchaseRequest->id)
            ->with('success', "PR {$purchaseRequest->pr_no} berhasil direvisi dan dikirim ulang ke QAD (nomor requisition tetap sama).");
    }

    public function reject(Request $request, ProcurementRequest $procurementRequest)
    {
        $user = auth()->user();

        if (!$procurementRequest->canBeApprovedBy($user)) {
            return back()->with('error', 'Anda tidak berwenang menolak permintaan ini.');
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $procurementRequest->update([
            'status'         => 'rejected',
            'rejected_stage' => 'ga',
            'reject_reason'  => $request->reason,
        ]);

        return redirect()->route('ga.review.index')
            ->with('success', "Permintaan {$procurementRequest->req_no} ditolak.");
    }
}
