<?php

namespace App\Services\Qad;

use App\Models\QadItem;
use Illuminate\Support\Collection;
use SimpleXMLElement;

class QadItemService
{
    public function __construct(private readonly WsaService $wsa) {}

    public function raiseLimits(): void
    {
        ini_set('max_execution_time', '300');
        set_time_limit(300);
    }

    /**
     * Local-cache browse for the item picker — never calls QAD live.
     */
    public function browse(?string $term, int $limit = 30): Collection
    {
        return QadItem::query()->active()->search($term)->orderBy('description')->limit($limit)->get();
    }

    /**
     * Pull the item master (QAD Fixed Asset Register — t_fa_id is the item
     * number) via the WSA broker and upsert into qad_items.
     *
     * @return array{synced: int, created: int, updated: int}
     */
    public function sync(): array
    {
        $this->raiseLimits();

        [$rows] = $this->wsa->call('SDI_getFixedAsset');

        $now = now();
        $created = 0;
        $updated = 0;
        $batch = [];

        foreach ($rows as $row) {
            $code = $this->text($row, 't_fa_id');

            if ($code === null) {
                continue;
            }

            $disposalDate = $this->text($row, 't_fa_disp_dt');

            $batch[] = [
                'qad_code' => $code,
                'description' => $this->text($row, 't_fa_desc1'),
                'part_number' => null,
                'qad_group' => $this->text($row, 't_fa_facls_id'),
                'prod_line' => null,
                'qad_status' => $disposalDate ? 'DISPOSED' : 'ACTIVE',
                'location' => $this->text($row, 't_fa_faloc_id'),
                'is_active' => $disposalDate === null,
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

    private function text(SimpleXMLElement $row, string $field): ?string
    {
        $value = trim((string) ($row->{$field} ?? ''));

        return $value === '' ? null : $value;
    }
}
