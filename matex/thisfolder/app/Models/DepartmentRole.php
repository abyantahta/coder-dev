<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentRole extends Model
{
    protected $fillable = [
        'department_id', 'name', 'key', 'is_superuser',
        'level', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_superuser' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'dept_role_id');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class, 'actor_role_id');
    }
}
