<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QXtendService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $domain;
    private int    $timeout;

    public function __construct()
    {
        $this->baseUrl  = config('services.qxtend.base_url', '');
        $this->username = config('services.qxtend.username', '');
        $this->password = config('services.qxtend.password', '');
        $this->domain   = config('services.qxtend.domain', 'QAD');
        $this->timeout  = (int) config('services.qxtend.timeout', 30);
    }

    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->username);
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode("{$this->username}:{$this->password}"),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

    /**
     * Get item list from QAD via QXtend browse
     */
    public function getItems(int $pageSize = 100, int $page = 1): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/browse', [
                    'browseObject' => 'ItemMaster',
                    'filter'       => [],
                    'pageSize'     => $pageSize,
                    'page'         => $page,
                ]);

            if ($response->successful()) {
                return $response->json('data', []);
            }

            Log::error('QXtend getItems failed', ['status' => $response->status(), 'body' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('QXtend getItems exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get stock on hand for specific item from QAD
     */
    public function getItemStock(string $itemCode, string $warehouse = 'WH01'): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/browse', [
                    'browseObject' => 'StockOnHand',
                    'filter'       => [
                        ['field' => 'item_code', 'value' => $itemCode],
                        ['field' => 'warehouse', 'value' => $warehouse],
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json('data', []);
                return $data[0] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('QXtend getItemStock exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get QAD receipts for goods-in
     */
    public function getReceipts(string $fromDate, string $toDate, string $warehouse = 'WH01'): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/browse', [
                    'browseObject' => 'PurchaseReceipt',
                    'filter'       => [
                        ['field' => 'receipt_date', 'operator' => '>=', 'value' => $fromDate],
                        ['field' => 'receipt_date', 'operator' => '<=', 'value' => $toDate],
                        ['field' => 'warehouse',    'value' => $warehouse],
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('data', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('QXtend getReceipts exception: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get specific receipt detail
     */
    public function getReceiptDetail(string $receiptNo): ?array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/browse', [
                    'browseObject' => 'PurchaseReceiptDetail',
                    'filter'       => [
                        ['field' => 'receipt_no', 'value' => $receiptNo],
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('data', []);
            }

            return null;
        } catch (\Exception $e) {
            Log::error('QXtend getReceiptDetail exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Post inventory issue (goods out) to QAD
     */
    public function postInventoryIssue(array $data): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/transaction', [
                    'transactionType' => 'InventoryIssue',
                    'domain'          => $this->domain,
                    'data'            => $data,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'message' => $response->json('message', 'QXtend error')];
        } catch (\Exception $e) {
            Log::error('QXtend postInventoryIssue exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sync items from QAD to local database
     */
    public function syncItems(): array
    {
        $items = $this->getItems(500);
        $synced = 0;
        $errors = [];

        foreach ($items as $qadItem) {
            try {
                $item = \App\Models\Item::updateOrCreate(
                    ['item_code' => $qadItem['item_code'] ?? $qadItem['pt_part'] ?? ''],
                    [
                        'description'  => $qadItem['description'] ?? $qadItem['pt_desc1'] ?? '',
                        'unit'         => $qadItem['unit'] ?? $qadItem['pt_um'] ?? '',
                        'category'     => $qadItem['category'] ?? $qadItem['pt_group'] ?? '',
                        'last_sync_at' => now(),
                    ]
                );

                \App\Models\ItemStock::updateOrCreate(
                    ['item_id' => $item->id, 'warehouse' => $qadItem['warehouse'] ?? 'WH01'],
                    [
                        'qty_on_hand'  => $qadItem['qty_on_hand'] ?? 0,
                        'qty_reserved' => $qadItem['qty_reserved'] ?? 0,
                        'last_sync_at' => now(),
                    ]
                );

                $synced++;
            } catch (\Exception $e) {
                $errors[] = $qadItem['item_code'] ?? 'unknown';
            }
        }

        return ['synced' => $synced, 'errors' => $errors];
    }

    /**
     * Create Purchase Requisition di QAD setelah Director approve
     */
    public function createRequisition(array $data): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'QXtend belum dikonfigurasi', 'qad_req_no' => null];
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/transaction', [
                    'transactionType' => 'PurchaseRequisition',
                    'domain'          => $this->domain,
                    'data'            => $data,
                ]);

            if ($response->successful()) {
                return [
                    'success'    => true,
                    'qad_req_no' => $response->json('requisition_no') ?? $response->json('req_no') ?? null,
                    'message'    => 'Requisition berhasil dibuat di QAD',
                ];
            }

            Log::error('QXtend createRequisition failed', ['status' => $response->status(), 'body' => $response->body()]);
            return ['success' => false, 'message' => 'QXtend error: ' . $response->status(), 'qad_req_no' => null];
        } catch (\Exception $e) {
            Log::error('QXtend createRequisition exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'qad_req_no' => null];
        }
    }

    /**
     * Check connection to QXtend
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(5)
                ->get($this->baseUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
