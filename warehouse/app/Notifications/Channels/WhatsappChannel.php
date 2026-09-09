<?php

namespace App\Notifications\Channels;

use App\Services\WhatsappService;
use Illuminate\Notifications\Notification;

class WhatsappChannel
{
    public function __construct(private WhatsappService $whatsapp) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toWhatsapp')) {
            return;
        }

        $message = $notification->toWhatsapp($notifiable);
        $phone   = $notifiable->phone ?? null;

        $this->whatsapp->send($phone, $message);
    }
}
