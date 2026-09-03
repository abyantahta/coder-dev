<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceGroup extends Model
{
    protected $fillable = ['name', 'unit_id', 'description'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MaintenanceUnit::class, 'unit_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'group_id');
    }

    public function groupHead()
    {
        return $this->users()->where('role', 'group_head')->first();
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'group_id')->where('role', 'member');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'assigned_group_id');
    }

    public function getServiceRateAttribute(): float
    {
        $members = $this->members()->get();
        if ($members->isEmpty()) {
            return 0;
        }
        return round($members->map(fn ($m) => $m->service_rate)->average(), 1);
    }
}
