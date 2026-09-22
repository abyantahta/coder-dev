<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalService $service) {}

    public function advance(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        abort_unless($this->service->canAct(Auth::user(), $workOrder), 403, 'Kamu tidak punya akses untuk aksi ini.');
        $this->service->advance($workOrder, Auth::user(), $request);
        return redirect()->route('work-orders.show', $workOrder)->with('success', 'Tindakan berhasil diproses.');
    }

    public function reject(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $step = $workOrder->currentStep();
        abort_unless($step && $this->service->canAct(Auth::user(), $workOrder) && $step->can_reject, 403, 'Aksi reject tidak diizinkan.');
        $request->validate(['reason' => 'required|string|max:500']);
        $this->service->reject($workOrder, Auth::user(), $request->reason);
        return redirect()->route('work-orders.show', $workOrder)->with('success', 'WO berhasil ditolak.');
    }

    public function forward(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        $step = $workOrder->currentStep();
        abort_unless($step && $this->service->canAct(Auth::user(), $workOrder) && $step->can_forward, 403, 'Aksi forward tidak diizinkan.');
        $request->validate([
            'target_dept_id' => 'required|integer|exists:departments,id',
            'reason'         => 'required|string|max:500',
        ]);
        $this->service->forward($workOrder, Auth::user(), (int) $request->target_dept_id, $request->reason);
        return redirect()->route('work-orders.show', $workOrder)->with('success', 'WO berhasil diteruskan.');
    }

    public function cancel(Request $request, WorkOrder $workOrder): RedirectResponse
    {
        abort_unless($this->service->canCancel(Auth::user(), $workOrder), 403, 'Kamu tidak bisa membatalkan WO ini.');
        $request->validate(['reason' => 'required|string|max:500']);
        $this->service->cancel($workOrder, Auth::user(), $request->reason);
        return redirect()->route('work-orders.show', $workOrder)->with('success', 'WO berhasil dibatalkan.');
    }
}
