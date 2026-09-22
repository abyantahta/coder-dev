<?php

namespace App\Actions\DeliveryNote;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\PoStatus;
use App\Enums\ScheduleStatus;
use App\Models\DeliveryNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmShipment
{
    use LogsPoStatus;

    public function execute(DeliveryNote $dn, User $user): DeliveryNote
    {
        $schedule = $dn->deliverySchedule;
        $po = $dn->purchaseOrder;

        if ($schedule->status !== ScheduleStatus::Planned) {
            throw ValidationException::withMessages([
                'status' => 'Jadwal ini sudah dikonfirmasi pengirimannya.',
            ]);
        }

        if (! filled($dn->rm_sj_number) && ! filled($schedule->rm_sj_number)) {
            throw ValidationException::withMessages([
                'rm_sj_number' => 'Nomor surat jalan internal RM wajib diisi sebelum konfirmasi berangkat.',
            ]);
        }

        return DB::transaction(function () use ($dn, $schedule, $po, $user) {
            if (! filled($dn->rm_sj_number) && filled($schedule->rm_sj_number)) {
                $dn->update(['rm_sj_number' => $schedule->rm_sj_number]);
            }

            $schedule->update([
                'status' => ScheduleStatus::ShipConfirmed,
                'ship_confirmed_at' => now(),
                'ship_confirmed_by' => $user->id,
            ]);

            if (in_array($po->status, [PoStatus::Confirmed], true)) {
                $from = $po->status;
                $po->update(['status' => PoStatus::InProgress]);
                $this->logStatus(
                    $po,
                    $from,
                    PoStatus::InProgress,
                    'shipment_confirmed',
                    $user,
                    "DN {$dn->dn_number} dikirim ke OHP (SJ: {$dn->fresh()->rm_sj_number})"
                );
            } else {
                $this->logStatus(
                    $po,
                    $po->status,
                    $po->status,
                    'shipment_confirmed',
                    $user,
                    "DN {$dn->dn_number} dikirim ke OHP (SJ: {$dn->fresh()->rm_sj_number})"
                );
            }

            return $dn->fresh(['deliverySchedule', 'purchaseOrder']);
        });
    }
}
