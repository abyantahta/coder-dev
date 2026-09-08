<?php

namespace App\Services\Qad;

use App\Models\QadItem;
use Illuminate\Support\Collection;
use RuntimeException;

class QadItemService
{
    public function __construct(private readonly QadSoapClient $soap) {}

    /** Raw XML of the last SOAP response, kept for diagnosing an unverified endpoint. */
    private ?string $lastRaw = null;

    public function raiseLimits(): void
    {
        $seconds = max(60, (int) config('qad.timeout', 300));
        ini_set('max_execution_time', (string) $seconds);
        set_time_limit($seconds);
    }

    /**
     * Local-cache browse for the item picker — never calls QAD live.
     */
    public function browse(?string $term, int $limit = 30): Collection
    {
        return QadItem::query()->active()->search($term)->orderBy('description')->limit($limit)->get();
    }

    /**
     * Pull the full item master from QAD and upsert into qad_items.
     *
     * @return array{synced: int, created: int, updated: int}
     */
    public function sync(): array
    {
        $this->raiseLimits();

        $xml = $this->buildEnvelope('<wsat:SDI_getItemMasterExt/>');
        $body = $this->callAndBody($xml);
        $response = $body['SDI_getItemMasterExtResponse'] ?? null;

        if ($response === null) {
            throw new RuntimeException($this->faultMessage($body, 'SDI_getItemMasterExt'));
        }

        $rows = $this->normalizeRows($response['temp']['tempRow'] ?? []);
        $prodLines = config('qad.prod_lines', []); // empty = no filter
        $groupFilter = config('qad.group_filter');  // null/empty = no filter

        $now = now();
        $created = 0;
        $updated = 0;
        $batch = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            if ($prodLines && ! in_array($row['t_pt_prod_line'] ?? null, $prodLines, true)) {
                continue;
            }

            if ($groupFilter && ($row['t_pt_group'] ?? null) !== $groupFilter) {
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
        $existing = QadItem::whereIn('qad_code', $codes)->pluck('qad_code')->flip();

        $created = 0;
        $updated = 0;

        foreach ($batch as $row) {
            $existing->has($row['qad_code']) ? $updated++ : $created++;
        }

        QadItem::upsert($batch, uniqueBy: ['qad_code'], update: [
            'description', 'part_number', 'qad_group', 'prod_line',
            'qad_status', 'location', 'is_active', 'last_synced_at', 'updated_at',
        ]);

        return [$created, $updated];
    }

    /**
     * Wraps an operation body fragment with the standard envelope AND the
     * session-context auth block CODER's QAD instance needs.
     *
     * IMPORTANT: the element names/nesting below are a best-effort
     * reconstruction based on the SDI_CreatePR SOAP example — verify against
     * the real endpoint before relying on this; if QAD faults with an auth
     * error, this is the one place to fix.
     */
    private function buildEnvelope(string $operationXml): string
    {
        $wsa = config('qad.ws_namespace');
        $domain = config('qad.domain');
        $user = config('qad.username');
        $pass = config('qad.password');
        $version = config('qad.version');

        return <<<XML
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                        xmlns:wsat="{$wsa}" xmlns:qcom="http://www.qad.com/custom">
                <soapenv:Header>
                    <qcom:QDocMsg>
                        <control>
                            <dsSessionContext>
                                <ttContext>
                                    <property><name>domain</name><value>{$domain}</value></property>
                                    <property><name>userid</name><value>{$user}</value></property>
                                    <property><name>password</name><value>{$pass}</value></property>
                                    <property><name>version</name><value>{$version}</value></property>
                                </ttContext>
                            </dsSessionContext>
                        </control>
                    </qcom:QDocMsg>
                </soapenv:Header>
                <soapenv:Body>
                    {$operationXml}
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
