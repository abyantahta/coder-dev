<?php

namespace App\Notifications;

use App\Models\ProcurementRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class BudgetTopupNeededNotification extends Notification
{
    /**
     * @param Collection<int, \App\Models\ProcurementRequestDetail> $overBudgetDetails
     */
    public function __construct(private ProcurementRequest $procurementRequest, private Collection $overBudgetDetails) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'whatsapp'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pr = $this->procurementRequest;

        $mail = (new MailMessage)
            ->subject("Perlu Tambahan Budget: {$pr->req_no} ({$pr->department?->name})")
            ->greeting("Halo {$notifiable->name},")
            ->line("Section sudah menyetujui permintaan {$pr->req_no} dari {$pr->department?->name}, tapi ada item yang melebihi budget bulan ini:");

        foreach ($this->overBudgetDetails as $detail) {
            $mail->line("- {$detail->item->name}: qty {$detail->qty} {$detail->uom->code}");
        }

        return $mail->action('Kelola Budget', route('ga.budget.index'))
            ->line('Silakan tambahkan top-up budget bulan ini kalau disetujui, atau biarkan sesuai keputusan.');
    }

    public function toWhatsapp(object $notifiable): string
    {
        $pr = $this->procurementRequest;
        $items = $this->overBudgetDetails->pluck('item.name')->implode(', ');

        return "Halo {$notifiable->name}, permintaan {$pr->req_no} ({$pr->department?->name}) sudah disetujui section tapi melebihi budget untuk: {$items}. Cek halaman Budget untuk top-up.";
    }
}
