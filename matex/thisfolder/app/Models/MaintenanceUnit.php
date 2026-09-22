<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceUnit extends Model
{
    protected $fillable = ['name', 'description'];

    public function groups(): HasMany
    {
        return $this->hasMany(MaintenanceGroup::class, 'unit_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'unit_id');
    }

    public function unitHead()
    {
        return $this->users()->where('role', 'unit_head')->first();
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class, 'unit_id')->where('role', 'member');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'unit_id');
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
