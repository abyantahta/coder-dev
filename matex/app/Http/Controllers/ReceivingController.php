<?php

namespace App\Http\Controllers;

use App\Actions\Receiving\ReceiveToQad;
use App\Enums\ScheduleStatus;
use App\Http\Requests\ReceiveRequest;
use App\Models\DeliveryNote;
use App\Models\Receiving;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReceivingController extends Controller
{
    public function index(): Response
    {
        // status stays OhpOk until a DN's cumulative received qty reaches
        // its full qty (see ReceiveToQad), so this alone already excludes
        // fully-received DNs while still surfacing partially-received ones
        // for their remainder.
        $ready = DeliveryNote::query()
            ->with([
                'purchaseOrder.supplierRm',
                'purchaseOrderItem.item',
                'deliverySchedule.ohpSupplier',
                'ohpConfirmation',
                'receivings',
            ])
            ->whereHas('deliverySchedule', fn ($q) => $q->where('status', ScheduleStatus::OhpOk))
            ->latest()
            ->get()
            ->each->append(['received_qty', 'remaining_qty', 'is_fully_received']);

        $history = Receiving::query()
            ->with([
                'deliveryNote.purchaseOrder',
                'deliveryNote.purchaseOrderItem.item',
                'receiver',
            ])
            ->latest()
            ->paginate(15);

        return Inertia::render('Receiving/Index', [
            'ready' => $ready,
            'history' => $history,
            'receivingEnabled' => (bool) config('qad.receiving_enabled'),
        ]);
    }

    public function store(
        ReceiveRequest $request,
        DeliveryNote $deliveryNote,
        ReceiveToQad $action,
    ): RedirectResponse {
        $receiving = $action->execute(
            $deliveryNote,
            $request->user(),
            $request->validated('received_qty'),
            $request->validated('notes')
        );

        $message = $deliveryNote->fresh()->is_fully_received
            ? 'Receiving selesai (lunas) dan telah di-push ke QAD.'
            : 'Receiving parsial dicatat dan telah di-push ke QAD.';

        if ($receiving->qad_status?->value === 'failed') {
            $message = 'Receiving tercatat, tapi push ke QAD gagal — cek detail.';
        }

        return back()->with('success', $message);
    }
}
