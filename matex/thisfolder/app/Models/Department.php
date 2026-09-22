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
