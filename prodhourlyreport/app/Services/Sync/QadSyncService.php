<?php

namespace App\Services\Sync;

use App\Enums\SyncSource;
use App\Models\Line;
use App\Models\Product;
use App\Models\ProductModel;
use RuntimeException;

/**
 * Sync Lines and Products (items) from QAD via SOAP WSA.
 *
 * Ported from webprod-dev LineService::syncLines / ItemService::syncItems.
 * Product Models stay manual — they are the local link between a QAD Line
 * and QAD Products (Line → Model → Product).
 */
class QadSyncService
{
    public function __construct(
        private readonly QadSoapClient $soap,
    ) {}

    /**
     * Allow long-running QAD SOAP + upsert work (default 1 hour).
     */
    public function raiseLimits(): void
    {
        $seconds = (int) config('qad.timeout', 3600);

        if ($seconds < 1) {
            $seconds = 3600;
        }

        ini_set('max_execution_time', (string) $seconds);
        set_time_limit($seconds);
        ini_set('memory_limit', '1024M');
    }

    /**
     * @return array{model: string, source: string, synced: int, created: int, updated: int, status: string, message?: string}
     */
    public function syncLines(): array
    {
        $this->raiseLimits();

        $wsa = config('qad.ws_namespace');
        $domain = config('qad.domain');

        $xml = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                        xmlns:wsa="{$wsa}">
                <soapenv:Header/>
                <soapenv:Body>
                    <wsa:sankei_lnmstr>
                        <wsa:inpdomain>{$domain}</wsa:inpdomain>
                    </wsa:sankei_lnmstr>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;

        $body = $this->callAndBody($xml);
        $response = $body['sankei_lnmstrResponse'] ?? null;

        if ($response === null) {
            throw new RuntimeException($this->faultMessage($body, 'sankei_lnmstr'));
        }

        $rows = $this->normalizeRows($response['temp']['tempRow'] ?? []);

        $created = 0;
        $updated = 0;
        $now = now();

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $code = $this->soap->sanitizeValue($row['t_line_code'] ?? null);

            if (blank($code)) {
                continue;
            }

            $line = Line::updateOrCreate(
                ['qad_code' => $code],
                [
                    'name' => $code,
                    'code' => $code,
                    'is_active' => true,
                    'source' => SyncSource::Qad,
                    'last_synced_at' => $now,
                ],
            );

            $line->wasRecentlyCreated ? $created++ : $updated++;
        }

        return $this->result(Line::class, $created, $updated);
    }

    /**
     * Models are local grouping only — not sourced from QAD.
     *
     * @return array{model: string, source: string, synced: int, created: int, updated: int, status: string, message?: string}
     */
    public function syncProductModels(): array
    {
        return [
            'model' => ProductModel::class,
            'source' => SyncSource::Qad->value,
            'synced' => 0,
            'created' => 0,
            'updated' => 0,
            'status' => 'skipped',
            'message' => 'Product models are managed locally (Line → Model → Product).',
        ];
    }

    /**
     * @return array{model: string, source: string, synced: int, created: int, updated: int, status: string, message?: string}
     */
    public function syncProducts(): array
    {
        $this->raiseLimits();

        $wsa = config('qad.ws_namespace');
        $prodLines = config('qad.prod_lines', []);

        $xml = <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsat="{$wsa}">
                <soapenv:Header/>
                <soapenv:Body>
                    <wsat:SDI_getItemMasterExt/>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;

        $body = $this->callAndBody($xml);
        $response = $body['SDI_getItemMasterExtResponse'] ?? null;

        if ($response === null) {
            throw new RuntimeException($this->faultMessage($body, 'SDI_getItemMasterExt'));
        }

        $rows = $this->normalizeRows($response['temp']['tempRow'] ?? []);
        unset($body, $response);

        $now = now();
        $created = 0;
        $updated = 0;
        $batch = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (! in_array($row['t_pt_prod_line'] ?? null, $prodLines, true)) {
                continue;
            }

            $itemNumber = $this->soap->sanitizeValue($row['t_pt_part'] ?? null);

            if (blank($itemNumber)) {
                continue;
            }

            $partNumber = $this->soap->sanitizeValue($row['t_pt_desc2'] ?? null);
            $description = $this->soap->sanitizeValue($row['t_pt_desc1'] ?? null);

            $batch[] = [
                'qad_code' => $itemNumber,
                'name' => $partNumber ?: $itemNumber,
                'code' => $itemNumber,
                'part_number' => $partNumber,
                'description' => $description,
                'group' => $this->soap->sanitizeValue($row['t_pt_group'] ?? null),
                'category' => $this->soap->sanitizeValue($row['t_pt_prod_line'] ?? null),
                'location' => $this->soap->sanitizeValue($row['t_pt_location'] ?? null),
                'qad_status' => $this->soap->sanitizeValue($row['t_pt_status'] ?? null),
                'source' => SyncSource::Qad->value,
                'last_synced_at' => $now,
                'is_active' => true,
                'product_model_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 200) {
                [$c, $u] = $this->upsertProductBatch($batch);
                $created += $c;
                $updated += $u;
                $batch = [];
            }
        }

        unset($rows);

        if ($batch !== []) {
            [$c, $u] = $this->upsertProductBatch($batch);
            $created += $c;
            $updated += $u;
        }

        return $this->result(Product::class, $created, $updated);
    }

    /**
     * Upsert a chunk. Preserves existing product_model_id / is_active on update.
     *
     * @param  list<array<string, mixed>>  $batch
     * @return array{0: int, 1: int} created, updated
     */
    private function upsertProductBatch(array $batch): array
    {
        $codes = array_column($batch, 'qad_code');
        $existing = Product::query()
            ->whereIn('qad_code', $codes)
            ->pluck('qad_code')
            ->all();
        $existingSet = array_flip($existing);

        $created = 0;
        $updated = 0;

        foreach ($batch as &$row) {
            if (isset($existingSet[$row['qad_code']])) {
                unset($row['product_model_id'], $row['is_active'], $row['created_at']);
                $updated++;
            } else {
                $created++;
            }
        }
        unset($row);

        Product::upsert(
            $batch,
            uniqueBy: ['qad_code'],
            update: [
                'name',
                'code',
                'part_number',
                'description',
                'group',
                'category',
                'location',
                'qad_status',
                'source',
                'last_synced_at',
                'updated_at',
            ],
        );

        return [$created, $updated];
    }

    /**
     * @return array<string, mixed>
     */
    private function callAndBody(string $xml): array
    {
        $response = $this->soap->call($xml);

        if ($response['is_error']) {
            throw new RuntimeException($response['message'] ?? 'QAD SOAP call failed.');
        }

        $data = $response['data'] ?? [];

        foreach ($data as $key => $value) {
            if (is_string($key) && (str_ends_with($key, ':Envelope') || $key === 'Envelope') && is_array($value)) {
                $data = $value;
                break;
            }
        }

        foreach ($data as $key => $value) {
            if (is_string($key) && (str_ends_with($key, ':Body') || $key === 'Body') && is_array($value)) {
                return $value;
            }
        }

        return [];
    }

    private function faultMessage(array $body, string $operation): string
    {
        $fault = $body['SOAP-ENV:Fault']['detail']['ns1:FaultDetail']['errorMessage']
            ?? $body['SOAP-ENV:Fault']['faultstring']
            ?? null;

        return $fault
            ? "QAD Message ({$operation}): {$fault}"
            : "Unexpected QAD response for {$operation}.";
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(mixed $rows): array
    {
        if (! is_array($rows) || $rows === []) {
            return [];
        }

        if (array_is_list($rows)) {
            return array_values(array_filter($rows, 'is_array'));
        }

        return [$rows];
    }

    /**
     * @return array{model: string, source: string, synced: int, created: int, updated: int, status: string}
     */
    private function result(string $model, int $created, int $updated): array
    {
        return [
            'model' => $model,
            'source' => SyncSource::Qad->value,
            'synced' => $created + $updated,
            'created' => $created,
            'updated' => $updated,
            'status' => 'ok',
        ];
    }
}
