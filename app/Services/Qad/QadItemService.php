<?php

namespace App\Services\Qad;

use App\Models\QadItem;
use App\Models\Qxwsas;
use Illuminate\Support\Collection;
use RuntimeException;

class QadItemService
{
    public function __construct(private readonly QadSoapClient $soap) {}

    /** Raw XML of the last SOAP response, kept for diagnosing an unverified endpoint. */
    private ?string $lastRaw = null;

    /**
     * Allow long-running QAD SOAP + upsert work — the full item master can
     * be a large pull (mirrors prodhourlyreport's QadSyncService).
     */
    public function raiseLimits(): void
    {
        ini_set('max_execution_time', '3600');
        set_time_limit(3600);
        ini_set('memory_limit', '1024M');
    }

    /**
     * Local-cache browse for the item picker — never calls QAD live.
     */
    public function browse(?string $term, int $limit = 30): Collection
    {
        return QadItem::query()->active()->search($term)->orderBy('description')->limit($limit)->get();
    }

    /**
     * Pull the full item master from QAD (SDI_getItemMasterExt) and upsert
     * into qad_items. Connection details come from the qxwsas table.
     *
     * @return array{synced: int, created: int, updated: int}
     */
    public function sync(): array
    {
        $this->raiseLimits();

        $wsa = Qxwsas::firstOrFail();

        $xml = $this->buildEnvelope($wsa->qxwsa_wsa_path);
        $body = $this->callAndBody($xml, $wsa->qxwsa_wsa_url);
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

            $code = $this->soap->sanitizeValue($row['t_pt_part'] ?? null);

            if (blank($code)) {
                continue;
            }

            $batch[] = [
                'qad_code' => $code,
                'description' => $this->soap->sanitizeValue($row['t_pt_desc1'] ?? null),
                'part_number' => $this->soap->sanitizeValue($row['t_pt_desc2'] ?? null),
                'qad_group' => $this->soap->sanitizeValue($row['t_pt_group'] ?? null),
                'prod_line' => $this->soap->sanitizeValue($row['t_pt_prod_line'] ?? null),
                'qad_status' => $this->soap->sanitizeValue($row['t_pt_status'] ?? null),
                'location' => $this->soap->sanitizeValue($row['t_pt_location'] ?? null),
                'is_active' => true,
                'last_synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 200) {
                [$c, $u] = $this->upsertBatch($batch);
                $created += $c;
                $updated += $u;
                $batch = [];
            }
        }

        unset($rows);

        if ($batch) {
            [$c, $u] = $this->upsertBatch($batch);
            $created += $c;
            $updated += $u;
        }

        return ['synced' => $created + $updated, 'created' => $created, 'updated' => $updated];
    }

    /**
     * Preserves a locally-set `is_active` on update (e.g. an item a
     * warehouse admin deactivated by hand) instead of forcing it back to
     * true on every sync — same guard prodhourlyreport applies to
     * `product_model_id` for its local-only fields.
     *
     * @return array{0: int, 1: int} created, updated
     */
    private function upsertBatch(array $batch): array
    {
        $codes = array_column($batch, 'qad_code');
        $existing = QadItem::whereIn('qad_code', $codes)->pluck('qad_code')->flip();

        $created = 0;
        $updated = 0;

        foreach ($batch as $row) {
            $existing->has($row['qad_code']) ? $updated++ : $created++;
        }

        QadItem::upsert($batch, uniqueBy: ['qad_code'], update: [
            'description', 'part_number', 'qad_group', 'prod_line',
            'qad_status', 'location', 'last_synced_at', 'updated_at',
        ]);

        return [$created, $updated];
    }

    /**
     * Envelope shape matches prodhourlyreport's verified SDI_getItemMasterExt
     * call — no auth/session header block, just the WSA namespace.
     */
    private function buildEnvelope(string $namespace): string
    {
        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsat="{$namespace}">
                <soapenv:Header/>
                <soapenv:Body>
                    <wsat:SDI_getItemMasterExt/>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    /**
     * @return array<string, mixed>
     */
    private function callAndBody(string $xml, string $url): array
    {
        $response = $this->soap->call($xml, $url);
        $this->lastRaw = $response['raw'] ?? null;

        if ($response['is_error']) {
            $message = $response['message'] ?? 'QAD SOAP call failed.';
            if ($this->lastRaw) {
                $message .= ' | Raw response: '.$this->truncate($this->lastRaw);
            }
            throw new RuntimeException($message);
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

        if ($fault) {
            return "QAD Message ({$operation}): {$fault}";
        }

        $message = "Unexpected QAD response for {$operation} — expected key '{$operation}Response' not found.";

        if ($this->lastRaw) {
            $message .= ' | Raw response: '.$this->truncate($this->lastRaw);
        }

        return $message;
    }

    private function truncate(string $text, int $limit = 2000): string
    {
        return strlen($text) > $limit ? substr($text, 0, $limit).'…(truncated)' : $text;
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
}
