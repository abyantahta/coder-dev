<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name', 'code', 'slug', 'description', 'color',
        'is_active', 'accepts_work_orders', 'has_warehouse', 'has_unit_structure', 'completion_attachment_required',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'accepts_work_orders' => 'boolean',
        'has_warehouse'       => 'boolean',
        'has_unit_structure'  => 'boolean',
        'completion_attachment_required' => 'boolean',
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(DepartmentRole::class)->orderBy('sort_order');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(WoCategory::class)->orderBy('sort_order');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('step_order');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Departments a WO can be sent/forwarded to: active, marked by Super
     * Admin as "bisa menerima WO masuk", and with an approval flow
     * configured (without one a WO would sit with nobody to receive it).
     * Departments like Produksi/Engineering are send-only.
     */
    public function scopeReceivesWorkOrders($query)
    {
        return $query->where('is_active', true)
            ->where('accepts_work_orders', true)
            ->whereHas('approvalSteps');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'target_department_id');
    }

    public function colorClasses(): string
    {
        return match ($this->color) {
            'blue'   => 'tone-steel',
            'green'  => 'tone-forest',
            'purple' => 'tone-ink',
            'red'    => 'tone-brick',
            'orange' => 'tone-flame',
            'teal'   => 'tone-steel',
            default  => 'tone-neutral',
        };
    }
}
