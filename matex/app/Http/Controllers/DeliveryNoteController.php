<?php

namespace App\Http\Controllers;

use App\Actions\DeliveryNote\ConfirmByOhp;
use App\Actions\DeliveryNote\ConfirmShipment;
use App\Actions\DeliveryNote\GenerateDns;
use App\Enums\ScheduleStatus;
use App\Http\Requests\ConfirmOhpRequest;
use App\Http\Requests\GenerateDnRequest;
use App\Http\Requests\UpdateDnDeliveryDateRequest;
use App\Models\DeliveryNote;
use App\Models\DeliverySchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryNoteController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notes = DeliveryNote::query()
            ->visibleTo($user)
            ->with([
                'purchaseOrder.supplierRm',
                'purchaseOrderItem.item',
                'deliverySchedule.ohpSupplier',
                'ohpConfirmation',
                'receiving',
            ])
            ->when(
                $request->string('status')->isNotEmpty(),
                fn ($q) => $q->whereHas('deliverySchedule', fn ($sq) => $sq->where('status', $request->string('status')))
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Dn/Index', [
            'notes' => $notes,
            'filters' => [
                'status' => $request->string('status')->toString(),
            ],
            'scheduleStatuses' => collect(ScheduleStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function show(DeliveryNote $deliveryNote): Response
    {
        $this->authorize('view', $deliveryNote);

        $deliveryNote->load([
            'purchaseOrder.supplierRm',
            'purchaseOrderItem.item',
            'deliverySchedule.ohpSupplier',
            'ohpConfirmation.confirmer',
            'receivings.receiver',
        ]);
        $deliveryNote->append(['received_qty', 'remaining_qty', 'is_fully_received']);

        return Inertia::render('Dn/Show', [
            'note' => $deliveryNote,
            'sjUrl' => $deliveryNote->ohpConfirmation
                ? asset('storage/'.$deliveryNote->ohpConfirmation->sj_document_path)
                : null,
            'receivingEnabled' => (bool) config('qad.receiving_enabled'),
        ]);
    }

    public function print(DeliveryNote $deliveryNote): View
    {
        $this->authorize('print', $deliveryNote);

        $deliveryNote->load([
            'purchaseOrder.supplierRm',
            'purchaseOrderItem.item',
            'deliverySchedule.ohpSupplier',
        ]);

        return view('dn.print', ['note' => $deliveryNote]);
    }

    public function confirmShipment(DeliveryNote $deliveryNote, ConfirmShipment $action): RedirectResponse
    {
        $this->authorize('confirmShipment', $deliveryNote);
        $action->execute($deliveryNote, request()->user());

        return back()->with('success', 'Pengiriman ke Supplier OHP berhasil dikonfirmasi.');
    }

    public function generateDn(
        GenerateDnRequest $request,
        DeliverySchedule $delivery_schedule,
        GenerateDns $action,
    ): RedirectResponse {
        $delivery_schedule->load(['deliveryNote', 'purchaseOrder']);

        $action->executeForSchedule(
            $delivery_schedule,
            $request->validated('rm_sj_number'),
            $request->validated('delivery_date'),
        );

        // Stay on the PO page (not the new DN's page) — a PO often has
        // several schedules needing a DN each, so this lets Supplier RM
        // generate them one after another without navigating back and forth.
        return back()->with('success', 'DN berhasil digenerate. Silakan print/reprint lalu konfirmasi berangkat.');
    }

    public function updateDeliveryDate(
        UpdateDnDeliveryDateRequest $request,
        DeliveryNote $deliveryNote,
    ): RedirectResponse {
        $deliveryNote->update([
            'delivery_date' => $request->validated('delivery_date'),
        ]);

        return back()->with('success', 'Tanggal pengiriman DN diperbarui. Silakan reprint DN.');
    }

    public function confirmOhp(
        ConfirmOhpRequest $request,
        DeliveryNote $deliveryNote,
        ConfirmByOhp $action,
    ): RedirectResponse {
        $action->execute(
            $deliveryNote,
            $request->user(),
            $request->file('sj_document'),
            $request->validated('notes')
        );

        return back()->with('success', 'Konfirmasi OHP berhasil. Menunggu receiving PPIC.');
    }
}
