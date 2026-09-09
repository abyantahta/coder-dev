<?php

namespace App\Notifications;

use App\Models\PurchaseRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PrDeniedNotification extends Notification
{
    public function __construct(private PurchaseRequest $purchaseRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'whatsapp'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pr = $this->purchaseRequest;

        return (new MailMessage)
            ->subject("PR Ditolak Direktur: {$pr->pr_no} ({$pr->department?->name})")
            ->greeting("Halo {$notifiable->name},")
            ->line("PR {$pr->pr_no} untuk departemen {$pr->department?->name} ditolak oleh Direktur.")
            ->line("Alasan: {$pr->reject_reason}")
            ->action('Revisi & Kirim Ulang', route('ga.requests.show', $pr->id))
            ->line('Silakan revisi qty/item lalu kirim ulang — nomor requisition QAD yang sama akan dipakai lagi, tidak bikin nomor baru.');
    }

    public function toWhatsapp(object $notifiable): string
    {
        $pr = $this->purchaseRequest;

        return "Halo {$notifiable->name}, PR {$pr->pr_no} ({$pr->department?->name}) ditolak Direktur. Alasan: {$pr->reject_reason}. Silakan revisi & kirim ulang lewat web.";
    }
}
