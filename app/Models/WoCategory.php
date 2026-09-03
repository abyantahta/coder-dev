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
            'blue'   => 'bg-blue-100 text-blue-700',
            'green'  => 'bg-green-100 text-green-700',
            'yellow' => 'bg-yellow-100 text-yellow-700',
            'red'    => 'bg-red-100 text-red-700',
            'orange' => 'bg-orange-100 text-orange-700',
            'purple' => 'bg-purple-100 text-purple-700',
            default  => 'bg-slate-100 text-slate-700',
        };
    }
}
