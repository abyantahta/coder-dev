<?php

namespace App\Http\Controllers;

use App\Models\ProcurementRequest;
use App\Models\User;
use App\Notifications\BudgetTopupNeededNotification;
use App\Notifications\GaReviewNeededNotification;
use App\Notifications\SectionApprovalNeededNotification;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ProcurementApprovalController extends Controller
{
    public function __construct(private BudgetService $budgetService) {}

    /**
     * Approval tahap Section/Dept Head. Tahap GA ada di GaReviewController,
     * tahap Director ada di DirectorPurchaseRequestController.
     */
    public function index()
    {
        $user = auth()->user();

        $pending = ProcurementRequest::with(['user', 'department', 'details.item', 'details.uom', 'attachments'])
            ->where('status', 'pending_section')
            ->when(!$user->isSuperAdmin(), fn($q) => $q->whereIn('department_id', $user->sectionApprovalDepartmentIds()))
            ->latest()->get();

        $history = ProcurementRequest::with(['user', 'department', 'details.item', 'details.uom', 'attachments', 'aggregatedPr'])
            ->when(!$user->isSuperAdmin(), fn($q) => $q->whereIn('department_id', $user->sectionApprovalDepartmentIds()))
            ->whereIn('status', ['pending_ga', 'aggregated', 'rejected'])
            ->latest()->limit(50)->get();

        $budgetInfo = $this->buildBudgetInfo($pending->concat($history));

        return view('procurement.approvals.index', compact('pending', 'history', 'budgetInfo'));
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

    public function approve(Request $request, ProcurementRequest $procurementRequest)
    {
        $user = auth()->user();

        if (!$procurementRequest->canBeApprovedBy($user)) {
            return back()->with('error', 'Anda tidak berwenang menyetujui permintaan ini.');
        }

        $request->validate(['notes' => 'nullable|string|max:500']);

        $procurementRequest->update([
            'status'        => 'pending_ga',
            'section_by'    => $user->id,
            'section_at'    => now(),
            'section_notes' => $request->notes,
        ]);

        $gaUsers = User::where('role', 'ga')->where('is_active', true)->get();
        Notification::send($gaUsers, new GaReviewNeededNotification($procurementRequest));

        $procurementRequest->load('details.item', 'details.uom');
        $overBudget = $procurementRequest->details->where('is_over_budget', true);
        if ($overBudget->isNotEmpty()) {
            Notification::send($gaUsers, new BudgetTopupNeededNotification($procurementRequest, $overBudget));
        }

        return redirect()->route('procurement.approvals.index')
            ->with('success', "Permintaan {$procurementRequest->req_no} disetujui, diteruskan ke General Affair.");
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
            'rejected_stage' => 'section',
            'reject_reason'  => $request->reason,
        ]);

        return redirect()->route('procurement.approvals.index')
            ->with('success', "Permintaan {$procurementRequest->req_no} ditolak.");
    }
}
