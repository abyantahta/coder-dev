<?php

namespace App\Services\Qad;

use App\Models\QadSupplier;
use Illuminate\Support\Collection;
use RuntimeException;

class QadSupplierService
{
    public function __construct(private readonly QadWsaClient $soap) {}

    /** Raw XML of the last SOAP response, kept for diagnosing an unverified endpoint. */
    private ?string $lastRaw = null;

    public function raiseLimits(): void
    {
        ini_set('max_execution_time', '3600');
        set_time_limit(3600);
        ini_set('memory_limit', '1024M');
    }

    /**
     * Local-cache browse for a supplier picker — never calls QAD live.
     */
    public function browse(?string $term, int $limit = 30): Collection
    {
        return QadSupplier::query()->active()->search($term)->orderBy('name')->limit($limit)->get();
    }

    /**
     * Pull the full supplier (vendor address) master from QAD
     * (SDI_getSupplierMaster) and upsert into qad_suppliers.
     *
     * @return array{synced: int, created: int, updated: int}
     */
    public function sync(): array
    {
        $this->raiseLimits();

        $xml = $this->buildEnvelope(config('qad.wsa.namespace'));
        $body = $this->callAndBody($xml);
        $response = $body['SDI_getSupplierMasterResponse'] ?? null;

        if ($response === null) {
            throw new RuntimeException($this->faultMessage($body, 'SDI_getSupplierMaster'));
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

            $code = $this->soap->sanitizeValue($row['t_vd_addr'] ?? null);

            if (blank($code)) {
                continue;
            }

            $batch[] = [
                'qad_code' => $code,
                'name' => $this->soap->sanitizeValue($row['t_ad_sort'] ?? null),
                'address_line1' => $this->soap->sanitizeValue($row['t_ad_line1'] ?? null),
                'address_line2' => $this->soap->sanitizeValue($row['t_ad_line2'] ?? null),
                'city' => $this->soap->sanitizeValue($row['t_ad_city'] ?? null),
                'country' => $this->soap->sanitizeValue($row['t_ad_country'] ?? null),
                'contact_name' => $this->soap->sanitizeValue($row['t_ad_attn'] ?? null),
                'phone' => $this->soap->sanitizeValue($row['t_ad_phone'] ?? null),
                'email' => $this->soap->sanitizeValue($row['t_ad_email'] ?? null),
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
     * @return array{0: int, 1: int} created, updated
     */
    private function upsertBatch(array $batch): array
    {
        $codes = array_column($batch, 'qad_code');
        $existing = QadSupplier::whereIn('qad_code', $codes)->pluck('qad_code')->flip();

        $created = 0;
        $updated = 0;

        foreach ($batch as $row) {
            $existing->has($row['qad_code']) ? $updated++ : $created++;
        }

        QadSupplier::upsert($batch, uniqueBy: ['qad_code'], update: [
            'name', 'address_line1', 'address_line2', 'city', 'country',
            'contact_name', 'phone', 'email', 'last_synced_at', 'updated_at',
        ]);

        return [$created, $updated];
    }

    private function buildEnvelope(string $namespace): string
    {
        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsat="{$namespace}">
                <soapenv:Header/>
                <soapenv:Body>
                    <wsat:SDI_getSupplierMaster/>
                </soapenv:Body>
            </soapenv:Envelope>
        XML;
    }

    /**
     * @return array<string, mixed>
     */
    private function callAndBody(string $xml): array
    {
        $response = $this->soap->call($xml);
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
