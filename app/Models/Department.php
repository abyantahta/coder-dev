<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name', 'code', 'slug', 'description', 'color',
        'is_active', 'has_warehouse', 'has_unit_structure',
    ];

    protected $casts = [
        'is_active'           => 'boolean',
        'has_warehouse'       => 'boolean',
        'has_unit_structure'  => 'boolean',
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

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'target_department_id');
    }

    public function colorClasses(): string
    {
        return match ($this->color) {
            'blue'   => 'bg-blue-100 text-blue-700',
            'green'  => 'bg-green-100 text-green-700',
            'purple' => 'bg-purple-100 text-purple-700',
            'red'    => 'bg-red-100 text-red-700',
            'orange' => 'bg-orange-100 text-orange-700',
            'teal'   => 'bg-teal-100 text-teal-700',
            default  => 'bg-slate-100 text-slate-700',
        };
    }
}
