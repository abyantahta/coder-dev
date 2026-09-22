<?php

namespace App\Actions\DeliveryNote;

use App\Models\DeliveryNote;
use App\Models\DeliverySchedule;
use App\Models\PurchaseOrder;
use Illuminate\Validation\ValidationException;

class GenerateDns
{
    public function execute(PurchaseOrder $po): void
    {
        $po->loadMissing(['schedules.purchaseOrderItem', 'schedules.deliveryNote']);

        foreach ($po->schedules as $index => $schedule) {
            if ($schedule->deliveryNote) {
                continue;
            }

            if (! filled($schedule->rm_sj_number)) {
                throw ValidationException::withMessages([
                    'rm_sj_number' => "Jadwal #{$schedule->id} belum punya nomor surat jalan internal RM.",
                ]);
            }

            $this->createForSchedule($po, $schedule, $index + 1);
        }
    }

    public function executeForSchedule(
        DeliverySchedule $schedule,
        string $rmSjNumber,
        ?string $deliveryDate = null,
    ): DeliveryNote {
        $schedule->loadMissing(['deliveryNote', 'purchaseOrder', 'purchaseOrderItem']);

        if ($schedule->deliveryNote) {
            throw ValidationException::withMessages([
                'schedule' => 'DN untuk jadwal ini sudah digenerate.',
            ]);
        }

        $rmSjNumber = trim($rmSjNumber);
        if ($rmSjNumber === '') {
            throw ValidationException::withMessages([
                'rm_sj_number' => 'Nomor surat jalan internal RM wajib diisi.',
            ]);
        }

        $schedule->update(['rm_sj_number' => $rmSjNumber]);

        $po = $schedule->purchaseOrder;
        $seq = $po->schedules()->count();
        $existingCount = $po->deliveryNotes()->count();

        return $this->createForSchedule(
            $po,
            $schedule->fresh(),
            $existingCount + 1 ?: $seq,
            $deliveryDate,
        );
    }

    private function createForSchedule(
        PurchaseOrder $po,
        DeliverySchedule $schedule,
        int $seq,
        ?string $deliveryDate = null,
    ): DeliveryNote {
        $qty = (int) round((float) ($schedule->qty_confirmed ?? $schedule->qty));

        return DeliveryNote::create([
            'dn_number' => $this->makeDnNumber($po->po_number, $seq),
            'purchase_order_id' => $po->id,
            'delivery_schedule_id' => $schedule->id,
            'purchase_order_item_id' => $schedule->purchase_order_item_id,
            'qty' => $qty,
            'delivery_date' => $deliveryDate ?: $schedule->scheduled_date,
            'rm_sj_number' => $schedule->rm_sj_number,
            'generated_at' => now(),
        ]);
    }

    private function makeDnNumber(string $poNumber, int $seq): string
    {
        return sprintf('DN-%s-%03d', preg_replace('/[^A-Za-z0-9]/', '', $poNumber), $seq);
    }
}
