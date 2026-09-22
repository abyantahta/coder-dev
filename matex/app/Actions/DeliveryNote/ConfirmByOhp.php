<?php

namespace App\Actions\DeliveryNote;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\ScheduleStatus;
use App\Models\DeliveryNote;
use App\Models\OhpConfirmation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmByOhp
{
    use LogsPoStatus;

    public function execute(DeliveryNote $dn, User $user, UploadedFile $document, ?string $notes = null): DeliveryNote
    {
        $schedule = $dn->deliverySchedule;

        if ($schedule->status !== ScheduleStatus::ShipConfirmed) {
            throw ValidationException::withMessages([
                'status' => 'DN belum dalam status in-transit dari Supplier RM.',
            ]);
        }

        if ($dn->ohpConfirmation) {
            throw ValidationException::withMessages([
                'status' => 'DN sudah dikonfirmasi OHP.',
            ]);
        }

        return DB::transaction(function () use ($dn, $schedule, $user, $document, $notes) {
            $path = $document->store('sj-documents', 'public');

            OhpConfirmation::create([
                'delivery_note_id' => $dn->id,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
                'sj_document_path' => $path,
                'notes' => $notes,
            ]);

            $schedule->update([
                'status' => ScheduleStatus::OhpOk,
            ]);

            $po = $dn->purchaseOrder;
            $this->logStatus(
                $po,
                $po->status,
                $po->status,
                'ohp_confirmed',
                $user,
                "OHP mengonfirmasi OK untuk DN {$dn->dn_number}"
            );

            return $dn->fresh(['ohpConfirmation', 'deliverySchedule', 'purchaseOrderItem.item']);
        });
    }
}
