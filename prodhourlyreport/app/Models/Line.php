<?php

namespace App\Models;

use App\Enums\SyncSource;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'is_active', 'qad_code', 'source', 'last_synced_at'])]
class Line extends Model
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

    public function productModels(): HasMany
    {
        return $this->hasMany(ProductModel::class);
    }

    public function productionLogs(): HasMany
    {
        return $this->hasMany(ProductionLog::class);
    }

    /**
     * Leaders/group heads assigned to this line.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'line_user');
    }
}
