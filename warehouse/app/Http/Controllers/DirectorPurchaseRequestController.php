<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Notifications\PrDeniedNotification;
use App\Services\BudgetService;
use App\Services\QadSoapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class DirectorPurchaseRequestController extends Controller
{
    public function __construct(private QadSoapService $qadSoap, private BudgetService $budgetService) {}

    /**
     * GA sudah kirim PR ke QAD langsung saat agregasi — Direktur di sini
     * approve/deny, dan keduanya beneran push ke QAD (action Approval_PR).
     */
    public function index()
    {
        $pending = PurchaseRequest::with(['creator', 'department', 'details'])
            ->where('source', 'ga_aggregation')
            ->where('status', 'sent_to_qad')
            ->latest()->get();

        $history = PurchaseRequest::with(['creator', 'department'])
            ->where('source', 'ga_aggregation')
            ->whereIn('status', ['director_confirmed', 'director_denied', 'received', 'partially_received', 'distributing', 'completed', 'send_failed'])
            ->latest()->limit(50)->get();

        return view('director.purchase-requests.index', compact('pending', 'history'));
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load([
            'creator', 'department', 'details.procurementItem.uom',
            'details.sources.department', 'details.sources.procurementRequestDetail.request.attachments',
        ]);

        // Lampiran = gabungan dari semua ProcurementRequest asal yang di-agregasi jadi PR ini.
        $attachments = $purchaseRequest->sourceRequests()->with('attachments')->get()
            ->flatMap(fn($r) => $r->attachments)
            ->unique('id')
            ->values();

        // Budget vs actual 12 bulan terakhir, dijumlah semua item di PR ini, untuk departemen ini.
        $itemIds = $purchaseRequest->details->pluck('procurement_item_id')->unique();
        $budgetHistory = [];
        if ($purchaseRequest->department_id && $itemIds->isNotEmpty()) {
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $budget = 0;
                $consumed = 0;
                foreach ($itemIds as $itemId) {
                    $budget   += $this->budgetService->effectiveBudget($purchaseRequest->department_id, $itemId, $date->year, $date->month);
                    $consumed += $this->budgetService->consumed($purchaseRequest->department_id, $itemId, $date->year, $date->month);
                }
                $budgetHistory[] = [
                    'label'    => $date->translatedFormat('M Y'),
                    'budget'   => $budget,
                    'consumed' => $consumed,
                ];
            }
        }

        return view('director.purchase-requests.show', compact('purchaseRequest', 'attachments', 'budgetHistory'));
    }

    public function approve(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->source !== 'ga_aggregation' || $purchaseRequest->status !== 'sent_to_qad') {
            return back()->with('error', 'PR ini tidak dalam status yang bisa dikonfirmasi.');
        }

        $request->validate(['notes' => 'nullable|string|max:500']);
        $director = auth()->user();

        $result = $this->qadSoap->approveRequisition($purchaseRequest->qad_req_no, $director->qad_approver_code, $director->qad_password, '1', $request->notes, $purchaseRequest->qadBuyerCode());

        if (!$result['success']) {
            return back()->with('error', "Gagal kirim approval ke QAD: {$result['message']}");
        }

        $purchaseRequest->update([
            'status'           => 'director_confirmed',
            'director_by'      => $director->id,
            'director_at'      => now(),
            'director_notes'   => $request->notes,
            'qad_sync_message' => $result['message'],
        ]);

        return redirect()->route('director.purchase-requests.index')
            ->with('success', "PR {$purchaseRequest->pr_no} disetujui dan terkirim ke QAD.");
    }

    /**
     * Deny beneran push ke QAD (Action=2) dengan alasan wajib, lalu PR balik
     * ke GA untuk direvisi & dikirim ulang (pakai nomor requisition yang sama).
     */
    public function reject(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->source !== 'ga_aggregation' || $purchaseRequest->status !== 'sent_to_qad') {
            return back()->with('error', 'PR ini tidak dalam status yang bisa ditolak.');
        }

        $request->validate(['reason' => 'required|string|max:500']);
        $director = auth()->user();

        $result = $this->qadSoap->approveRequisition($purchaseRequest->qad_req_no, $director->qad_approver_code, $director->qad_password, '2', $request->reason);

        if (!$result['success']) {
            return back()->with('error', "Gagal kirim penolakan ke QAD: {$result['message']}");
        }

        $purchaseRequest->update([
            'status'           => 'director_denied',
            'director_by'      => $director->id,
            'director_at'      => now(),
            'reject_reason'    => $request->reason,
            'qad_sync_message' => $result['message'],
        ]);

        $gaUsers = User::where('role', 'ga')->where('is_active', true)->get();
        Notification::send($gaUsers, new PrDeniedNotification($purchaseRequest));

        return redirect()->route('director.purchase-requests.index')
            ->with('success', "PR {$purchaseRequest->pr_no} ditolak dan dikirim ke QAD. GA sudah diberi tahu untuk revisi.");
    }
}
