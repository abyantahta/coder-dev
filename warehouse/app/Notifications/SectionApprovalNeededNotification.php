<?php

namespace App\Notifications;

use App\Models\ProcurementRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SectionApprovalNeededNotification extends Notification
{
    public function __construct(private ProcurementRequest $procurementRequest) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'whatsapp'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pr = $this->procurementRequest;

        return (new MailMessage)
            ->subject("Persetujuan Diperlukan: Permintaan Kebutuhan GA {$pr->req_no}")
            ->greeting("Halo {$notifiable->name},")
            ->line("{$pr->user->name} dari departemen {$pr->department?->name} mengajukan permintaan kebutuhan GA {$pr->req_no}.")
            ->line("Tujuan: {$pr->purpose}")
            ->action('Review Permintaan', route('procurement.requests.show', $pr))
            ->line('Mohon segera ditinjau untuk approval.');
    }

    public function toWhatsapp(object $notifiable): string
    {
        $pr = $this->procurementRequest;

        return "Halo {$notifiable->name}, ada permintaan kebutuhan GA baru ({$pr->req_no}) dari {$pr->user->name} ({$pr->department?->name}) yang menunggu persetujuan Anda. Cek di aplikasi warehouse.";
    }
}
