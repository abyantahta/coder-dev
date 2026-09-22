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
