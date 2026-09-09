<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DirectorBudgetSummaryNotification extends Notification
{
    /**
     * @param Collection<int, \App\Models\PurchaseRequest> $purchaseRequests PR batch yang baru saja diproses GA (per departemen)
     */
    public function __construct(private Collection $purchaseRequests) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Ringkasan PR Kebutuhan GA — ' . now()->translatedFormat('d F Y H:i'))
            ->greeting("Halo {$notifiable->name},")
            ->line('GA baru saja memproses permintaan kebutuhan berikut, per departemen (sudah terkirim ke QAD):');

        foreach ($this->purchaseRequests as $pr) {
            $mail->line("**{$pr->department?->name}** — PR {$pr->pr_no} ({$pr->getStatusLabel()})");
            foreach ($pr->details as $detail) {
                $item  = $detail->procurementItem;
                $total = $detail->qty_needed * ($item->price ?? 0);
                $mail->line("  • {$item->name}: {$detail->qty_needed} {$item->uom->code} — Rp " . number_format($total, 0, ',', '.'));
            }
        }

        return $mail->action('Lihat & Konfirmasi PR', route('director.purchase-requests.index'))
            ->line('Silakan cek dan konfirmasi dari halaman Approval PR.');
    }
}
