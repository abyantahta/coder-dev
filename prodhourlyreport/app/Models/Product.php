<?php

namespace App\Models;

use App\Enums\SyncSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'product_model_id',
    'name',
    'code',
    'part_number',
    'description',
    'category',
    'group',
    'location',
    'qad_status',
    'is_active',
    'qad_code',
    'source',
    'last_synced_at',
])]
class Product extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'source' => SyncSource::class,
            'last_synced_at' => 'datetime',
        ];
    }

    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class);
    }
}
