<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    protected $fillable = [
        'department_id', 'step_order', 'name', 'actor_role_id',
        'step_type', 'can_reject', 'can_forward', 'can_assign',
        'assigns_to_role_key', 'action_label', 'reject_label',
        'auto_advance_hours',
    ];

    protected $casts = [
        'can_reject'  => 'boolean',
        'can_forward' => 'boolean',
        'can_assign'  => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function actorRole(): BelongsTo
    {
        return $this->belongsTo(DepartmentRole::class, 'actor_role_id');
    }

    public function isActedByRequester(): bool
    {
        return $this->step_type === 'requester_review';
    }

    public function isActedByAssignedMember(): bool
    {
        return $this->step_type === 'completion';
    }

    public function isActedByRole(): bool
    {
        return ! $this->isActedByRequester() && ! $this->isActedByAssignedMember();
    }
}
