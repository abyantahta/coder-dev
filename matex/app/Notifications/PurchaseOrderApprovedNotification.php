<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every Supplier RM user of a PO once Purchasing gives final OK
 * (ApproveByPurchasing) — the point where the ball moves to their side:
 * fill the internal SJ number and generate the DN. Independent of whether
 * the QAD push succeeded, since that's an internal sync concern, not
 * something the supplier needs to know about.
 */
class PurchaseOrderApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly PurchaseOrder $po) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $po = $this->po;

        $mail = (new MailMessage)
            ->subject("PO {$po->po_number} Dikonfirmasi Purchasing")
            ->greeting("Halo {$notifiable->name},")
            ->line("Purchase Order **{$po->po_number}** telah dikonfirmasi OK oleh Purchasing PT. Sankei Dharma Indonesia.")
            ->line('Rincian pesanan:');

        foreach ($po->items as $poItem) {
            $qty = $poItem->qty_confirmed ?? $poItem->qty_ordered;
            $mail->line("- {$poItem->item->item_number} ({$poItem->item->description}): {$qty} {$poItem->item->uom}");
        }

        return $mail
            ->line('Due date: '.$po->due_date->format('d M Y'))
            ->line('Mohon segera isi nomor Surat Jalan internal dan generate Delivery Note melalui portal Matex.')
            ->action('Buka PO di Portal Matex', route('purchase-orders.show', $po))
            ->line('Terima kasih atas kerja samanya.');
    }
}
