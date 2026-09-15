<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QadItem extends Model
{
    protected $fillable = [
        'qad_code', 'description', 'part_number', 'qad_group',
        'prod_line', 'qad_status', 'location', 'is_active', 'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(WoPartOrderLine::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** @return list<string> */
    public static function excludedProdLines(): array
    {
        return array_values(array_filter(array_map(
            static fn ($line) => strtoupper(trim((string) $line)),
            config('qad.excluded_prod_lines', ['FG', 'RM', 'SA'])
        )));
    }

    public static function isExcludedProdLine(?string $prodLine): bool
    {
        $normalized = strtoupper(trim((string) $prodLine));

        return $normalized !== '' && in_array($normalized, self::excludedProdLines(), true);
    }

    public function scopeWithoutExcludedProdLines($query)
    {
        $lines = self::excludedProdLines();
        if ($lines === []) {
            return $query;
        }

        $placeholders = implode(',', array_fill(0, count($lines), '?'));

        return $query->where(function ($q) use ($placeholders, $lines) {
            $q->whereNull('prod_line')
                ->orWhere('prod_line', '')
                ->orWhereRaw("UPPER(TRIM(prod_line)) NOT IN ({$placeholders})", $lines);
        });
    }

    public static function purgeExcludedProdLines(): int
    {
        $lines = self::excludedProdLines();
        if ($lines === []) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($lines), '?'));

        return static::whereRaw("UPPER(TRIM(prod_line)) IN ({$placeholders})", $lines)->delete();
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn ($q) => $q
            ->where('qad_code', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->orWhere('part_number', 'like', "%{$term}%"));
    }
}
