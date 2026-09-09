<?php

namespace App\Notifications;

use App\Models\ProcurementRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GaReviewNeededNotification extends Notification
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
            ->subject("Perlu Direview: Permintaan Kebutuhan GA {$pr->req_no}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Permintaan kebutuhan GA {$pr->req_no} dari departemen {$pr->department?->name} sudah disetujui {$pr->sectionBy?->name} dan siap direview.")
            ->action('Buka Dashboard Review GA', route('ga.review.index'))
            ->line('Silakan cek tab departemen atau tab Summary untuk memproses permintaan ini.');
    }

    public function toWhatsapp(object $notifiable): string
    {
        $pr = $this->procurementRequest;

        return "Halo {$notifiable->name}, permintaan kebutuhan GA {$pr->req_no} dari {$pr->department?->name} sudah disetujui dept head dan siap direview di dashboard GA.";
    }
}
