<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    private string $provider;
    private string $apiUrl;
    private string $apiKey;
    private string $sender;
    private int    $timeout;

    public function __construct()
    {
        $this->provider = config('services.whatsapp.provider', '');
        $this->apiUrl   = config('services.whatsapp.api_url', '');
        $this->apiKey   = config('services.whatsapp.api_key', '');
        $this->sender   = config('services.whatsapp.sender', '');
        $this->timeout  = (int) config('services.whatsapp.timeout', 15);
    }

    public function isConfigured(): bool
    {
        return !empty($this->provider) && !empty($this->apiUrl) && !empty($this->apiKey);
    }

    /**
     * Kirim pesan WA. Belum ada provider terpasang — kalau belum dikonfigurasi,
     * cukup log & skip (tidak melempar error), meniru pola QXtendService::isConfigured().
     */
    public function send(?string $phone, string $message): array
    {
        if (empty($phone)) {
            return ['success' => false, 'skipped' => true, 'message' => 'Nomor WhatsApp tujuan kosong'];
        }

        if (!$this->isConfigured()) {
            Log::info('WhatsApp not configured, skipping send', ['phone' => $phone]);
            return ['success' => false, 'skipped' => true, 'message' => 'WhatsApp provider belum dikonfigurasi'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept'        => 'application/json',
            ])->timeout($this->timeout)->post($this->apiUrl, [
                'sender'  => $this->sender,
                'target'  => $phone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            Log::error('WhatsApp send failed', ['status' => $response->status(), 'body' => $response->body()]);
            return ['success' => false, 'message' => 'WhatsApp API error: ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('WhatsApp send exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
