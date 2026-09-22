<?php

namespace App\Actions\Receiving;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\PoStatus;
use App\Enums\QadSyncStatus;
use App\Enums\ScheduleStatus;
use App\Models\DeliveryNote;
use App\Models\Receiving;
use App\Models\User;
use App\Services\Qad\QadClientInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceiveToQad
{
    use LogsPoStatus;

    public function __construct(private QadClientInterface $qad) {}

    /**
     * Records one receipt against a DN — can be partial (goods arriving in
     * installments across several trucks/dates). Each call pushes only the
     * qty received THIS time to QAD (receivePurchaseOrder reduces QAD's own
     * open qty incrementally), and the schedule only flips to Received once
     * the DN's cumulative received qty reaches its full qty.
     */
    public function execute(DeliveryNote $dn, User $user, ?float $receivedQty = null, ?string $notes = null): Receiving
    {
        // Same kill switch as DeliveryNotePolicy::receive() — checked here
        // too since this action could be invoked directly (tinker, a future
        // feature) bypassing the policy.
        if (! config('qad.receiving_enabled')) {
            throw ValidationException::withMessages([
                'status' => 'Receiving sementara dinonaktifkan.',
            ]);
        }

        $schedule = $dn->deliverySchedule;

        if ($schedule->status !== ScheduleStatus::OhpOk) {
            throw ValidationException::withMessages([
                'status' => 'DN belum dikonfirmasi OK oleh OHP.',
            ]);
        }

        $alreadyReceived = (int) $dn->receivings()->sum('received_qty');
        $remaining = (int) $dn->qty - $alreadyReceived;

        if ($remaining <= 0) {
            throw ValidationException::withMessages([
                'status' => 'DN sudah diterima penuh.',
            ]);
        }

        $qty = $receivedQty !== null ? (float) $receivedQty : (float) $remaining;

        if ($qty > $remaining) {
            throw ValidationException::withMessages([
                'received_qty' => "Qty melebihi sisa yang belum diterima ({$remaining} kg).",
            ]);
        }

        return DB::transaction(function () use ($dn, $schedule, $user, $qty, $notes, $alreadyReceived) {
            $receiving = Receiving::create([
                'delivery_note_id' => $dn->id,
                'delivery_schedule_id' => $schedule->id,
                'received_qty' => $qty,
                'received_by' => $user->id,
                'received_at' => now(),
                'qad_status' => QadSyncStatus::Pending,
                'notes' => $notes,
            ]);

            $result = $this->qad->receive($receiving);

            $receiving->update([
                'qad_status' => $result['success'] ? QadSyncStatus::Success : QadSyncStatus::Failed,
                'qad_payload' => $result['payload'],
                'qad_response' => $result['response'],
            ]);

            $isFullyReceived = ($alreadyReceived + $qty) >= (int) $dn->qty;

            if ($isFullyReceived) {
                $schedule->update(['status' => ScheduleStatus::Received]);
            }

            $po = $dn->purchaseOrder->fresh(['schedules']);
            $allReceived = $isFullyReceived && $po->schedules->every(
                fn ($s) => $s->status === ScheduleStatus::Received
            );

            if ($allReceived) {
                $from = $po->status;
                $po->update(['status' => PoStatus::Completed]);
                $this->logStatus(
                    $po,
                    $from,
                    PoStatus::Completed,
                    'received',
                    $user,
                    "Receiving DN {$dn->dn_number} — seluruh jadwal selesai"
                );
            } else {
                $note = $isFullyReceived
                    ? "Receiving DN {$dn->dn_number} (lunas) dan push ke QAD"
                    : "Receiving parsial DN {$dn->dn_number} — {$qty} kg (sisa ".max(0, (int) $dn->qty - $alreadyReceived - $qty)." kg) dan push ke QAD";

                $this->logStatus(
                    $po,
                    $po->status,
                    $po->status,
                    'received',
                    $user,
                    $note
                );
            }

            return $receiving->fresh(['deliveryNote', 'receiver']);
        });
    }
}
