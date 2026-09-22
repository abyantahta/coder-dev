<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoCategory extends Model
{
    protected $fillable = [
        'department_id', 'name', 'description',
        'leadtime_days', 'color', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function colorClasses(): string
    {
        return match ($this->color) {
            'blue'   => 'tone-steel',
            'green'  => 'tone-forest',
            'yellow' => 'tone-gold',
            'red'    => 'tone-brick',
            'orange' => 'tone-flame',
            'purple' => 'tone-ink',
            default  => 'tone-neutral',
        };
    }
}
