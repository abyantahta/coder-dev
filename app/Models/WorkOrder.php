<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkOrder extends Model
{
    protected $fillable = [
        'wo_number', 'title', 'description', 'category', 'priority',
        'requester_id', 'destination', 'status',
        'target_department_id', 'wo_category_id', 'current_step_order', 'leadtime_days',
        'forwarded_to', 'forward_reason',
        'unit_id', 'assigned_group_id', 'assigned_member_id', 'accepted_by',
        'accepted_at', 'assigned_group_at', 'scheduled_start_at', 'deadline', 'parts_ready_at',
        'planned_start_at', 'planned_end_at', 'actual_start_at', 'actual_end_at',
        'completed_at',
        'rework_count', 'rework_requested_at', 'rework_deadline',
        'finished_at', 'score',
        'rejection_reason', 'review_note',
        'completion_note', 'completion_image_path',
        'attachment_path', 'attachment_name', 'attachment_type',
    ];

    protected $casts = [
        'accepted_at'        => 'datetime',
        'assigned_group_at'  => 'datetime',
        'scheduled_start_at' => 'datetime',
        'deadline'           => 'datetime',
        'parts_ready_at'     => 'datetime',
        'planned_start_at'   => 'datetime',
        'planned_end_at'     => 'datetime',
        'actual_start_at'    => 'datetime',
        'actual_end_at'      => 'datetime',
        'completed_at'       => 'datetime',
        'rework_requested_at'=> 'datetime',
        'rework_deadline'    => 'datetime',
        'finished_at'        => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function targetDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function woCategory(): BelongsTo
    {
        return $this->belongsTo(WoCategory::class);
    }

    public function currentStep(): ?ApprovalStep
    {
        if ($this->current_step_order === null || $this->target_department_id === null) {
            return null;
        }
        return ApprovalStep::where('department_id', $this->target_department_id)
            ->where('step_order', $this->current_step_order)
            ->first();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MaintenanceUnit::class, 'unit_id');
    }

    public function assignedGroup(): BelongsTo
    {
        return $this->belongsTo(MaintenanceGroup::class, 'assigned_group_id');
    }

    public function assignedMember(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_member_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(WoHistory::class, 'wo_id')->orderBy('created_at');
    }

    public function spareParts(): HasMany
    {
        return $this->hasMany(WoSparePart::class, 'wo_id');
    }

    public function partOrder(): HasOne
    {
        return $this->hasOne(WoPartOrder::class, 'wo_id')->latestOfMany();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public static function generateWoNumber(Department|string|null $department = null): string
    {
        $code = self::deptCode($department);
        $prefix = 'WO-' . $code . '-' . now()->format('Ym') . '-';
        $last = static::where('wo_number', 'like', $prefix . '%')
            ->orderByDesc('wo_number')
            ->value('wo_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public static function deptCode(Department|string|null $department = null): string
    {
        if ($department instanceof Department) {
            return strtoupper($department->code ?: 'MTC');
        }

        return match (strtolower((string) $department)) {
            'maintenance', 'mtc' => 'MTC',
            'ga' => 'GA',
            'qa' => 'QA',
            '' => 'MTC',
            default => strtoupper($department),
        };
    }

    public function addHistory(int $userId, string $action, string $description = ''): void
    {
        $this->histories()->create([
            'user_id'     => $userId,
            'action'      => $action,
            'description' => $description,
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->deadline
            && $this->clockAsOf()->isAfter($this->deadline)
            && ! in_array($this->status, ['finished', 'cancelled', 'rejected', 'forwarded_ga', 'forwarded_qa', 'forwarded_maintenance']);
    }

    /** Clock stops once the member marks the WO complete, waiting for requester review. */
    public function isClockFrozen(): bool
    {
        return $this->status === 'completed';
    }

    public function clockAsOf(): Carbon
    {
        if ($this->isClockFrozen()) {
            return $this->actual_end_at ?? $this->completed_at ?? now();
        }

        return now();
    }

    /** Snapshot original plan. Rework extra time extends `deadline` only, not the plan. */
    public function planSnapshot(?Carbon $start, ?Carbon $end, bool $overwrite = false): array
    {
        if (! $end) {
            return [];
        }

        $start ??= now();
        $data = [];

        if ($overwrite || ! $this->planned_end_at) {
            $data['planned_start_at'] = $start;
            $data['planned_end_at'] = $end;
        }
        if ($overwrite || ! $this->actual_start_at) {
            $data['actual_start_at'] = $start;
        }

        return $data;
    }

    public function freezeActualEnd(?Carbon $at = null): array
    {
        $at ??= now();

        return [
            'completed_at'  => $at,
            'actual_end_at' => $at,
        ];
    }

    public function unfreezeForRework(): array
    {
        return [
            'completed_at'  => null,
            'actual_end_at' => null,
        ];
    }

    public function clearPlan(): array
    {
        return [
            'planned_start_at' => null,
            'planned_end_at'   => null,
            'actual_start_at'  => null,
            'actual_end_at'    => null,
            'completed_at'     => null,
        ];
    }

    public function needsPartsCheck(): bool
    {
        return $this->status === 'accepted' && $this->spareParts()->count() === 0;
    }

    public function hasUnavailableParts(): bool
    {
        return $this->spareParts()->where('is_available', false)->exists();
    }

    public function calculateScore(): int
    {
        $completedAt = $this->completed_at ?? now();
        $deadline    = $this->deadline ?? $completedAt;

        $daysLate     = max(0, $completedAt->diffInDays($deadline, false) * -1);
        $timePenalty  = (int) ($daysLate * 10);
        $reworkPenalty = $this->rework_count * 15;

        return max(10, 100 - $timePenalty - $reworkPenalty);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending($q)  { return $q->where('status', 'pending'); }
    public function scopeActive($q)   { return $q->whereNotIn('status', ['finished', 'cancelled', 'rejected', 'forwarded_ga', 'forwarded_qa', 'forwarded_maintenance']); }
    public function scopeFinished($q) { return $q->where('status', 'finished'); }

    /** WO the user may see on /work-orders. */
    public function scopeVisibleTo($q, User $user)
    {
        if ($user->isUnitHead()) {
            return $q->where(fn ($inner) => $inner
                ->where('destination', 'maintenance')
                ->orWhere('requester_id', $user->id));
        }

        if ($user->isGroupHead()) {
            return $q->where(fn ($inner) => $inner
                ->where(fn ($q2) => $q2
                    ->where('destination', 'maintenance')
                    ->where('assigned_group_id', $user->group_id))
                ->orWhere('requester_id', $user->id)
                ->orWhere(fn ($q2) => $q2
                    ->where('destination', 'maintenance')
                    ->whereNotNull('assigned_group_id')
                    ->where('assigned_group_id', '!=', $user->group_id)
                    ->whereNotIn('status', ['pending', 'accepted', 'rejected', 'pending_parts', 'parts_ordered', 'parts_received'])));
        }

        if ($user->isSectionHead()) {
            return $q->where(fn ($inner) => $inner
                ->whereIn('destination', ['maintenance', 'qa', 'ga'])
                ->orWhere('requester_id', $user->id));
        }

        if ($user->isMember() || $user->isQaMember()) {
            return $q->where(fn ($inner) => $inner
                ->where('assigned_member_id', $user->id)
                ->orWhere('requester_id', $user->id));
        }

        if ($user->isWarehouseMtc()) {
            return $q->where(fn ($inner) => $inner
                ->where(fn ($q2) => $q2
                    ->whereIn('status', ['pending_parts', 'parts_ordered', 'parts_received'])
                    ->where('destination', 'maintenance'))
                ->orWhere('requester_id', $user->id));
        }

        if ($user->isQaGroupHead() || $user->isQaSectionHead()) {
            return $q->where(fn ($inner) => $inner
                ->where('destination', 'qa')
                ->orWhere('requester_id', $user->id));
        }

        if ($user->isGaSectionHead()) {
            return $q->where(fn ($inner) => $inner
                ->where('destination', 'ga')
                ->orWhere('requester_id', $user->id));
        }

        return $q->where('requester_id', $user->id);
    }

    /**
     * WO whose next action sits on this user's desk (matches ApprovalService::canAct
     * plus warehouse queue). Closed / forwarded statuses are excluded.
     */
    public function scopeWaitingOn($q, User $user)
    {
        $closed = ['finished', 'cancelled', 'rejected', 'forwarded_ga', 'forwarded_qa', 'forwarded_maintenance'];

        return $q->where(function ($outer) use ($user, $closed) {
            $outer->where(function ($approval) use ($user, $closed) {
                $approval->whereNotIn('status', array_merge($closed, ['pending_parts', 'parts_ordered']))
                    ->whereNotNull('target_department_id')
                    ->whereNotNull('current_step_order')
                    ->whereExists(function ($sub) use ($user) {
                        $sub->selectRaw('1')
                            ->from('approval_steps')
                            ->whereColumn('approval_steps.department_id', 'work_orders.target_department_id')
                            ->whereColumn('approval_steps.step_order', 'work_orders.current_step_order')
                            ->where(function ($step) use ($user) {
                                $step->where(function ($s) use ($user) {
                                    $s->where('approval_steps.step_type', 'requester_review')
                                        ->where('work_orders.status', 'completed')
                                        ->where('work_orders.requester_id', $user->id);
                                })->orWhere(function ($s) use ($user) {
                                    $s->where('approval_steps.step_type', 'completion')
                                        ->whereIn('work_orders.status', ['assigned_member', 'parts_received', 'rework'])
                                        ->where('work_orders.assigned_member_id', $user->id);
                                })->orWhere(function ($s) use ($user) {
                                    $s->where('approval_steps.step_type', 'material_check')
                                        ->where('work_orders.status', 'assigned_member')
                                        ->where('work_orders.assigned_member_id', $user->id);
                                });

                                if ($user->dept_role_id) {
                                    $step->orWhere(function ($s) use ($user) {
                                        $s->whereNotIn('approval_steps.step_type', ['requester_review', 'completion', 'material_check'])
                                            ->whereNotNull('approval_steps.actor_role_id')
                                            ->where('approval_steps.actor_role_id', $user->dept_role_id);
                                        // Mirrors ApprovalService::withinAssignedGroup().
                                        if ($user->group_id !== null) {
                                            $s->where(fn ($g) => $g
                                                ->where('approval_steps.step_type', '!=', 'assign')
                                                ->orWhereNull('work_orders.assigned_group_id')
                                                ->orWhere('work_orders.assigned_group_id', $user->group_id));
                                        }
                                    });
                                }
                            });
                    });
            });

            if ($user->canActAsWarehouse() && $user->department_id) {
                $outer->orWhere(function ($wh) use ($user) {
                    $wh->whereIn('status', ['pending_parts', 'parts_ordered'])
                        ->where('target_department_id', $user->department_id);
                });
            }
        });
    }

    public function neededActionLabel(): string
    {
        if ($this->status === 'pending_parts') {
            return 'Kelola pemesanan part';
        }
        if ($this->status === 'parts_ordered') {
            return 'Konfirmasi barang tiba';
        }

        $step = $this->currentStep();
        if (! $step) {
            return 'Buka detail';
        }

        return match ($step->step_type) {
            'standard'          => $step->action_label ?: 'Terima WO',
            'spare_parts_check' => 'Cek sparepart',
            'assign'            => $step->action_label ?: 'Assign',
            'material_check'    => 'Cek material',
            'completion'        => $this->status === 'rework' ? 'Selesaikan rework' : 'Tandai selesai',
            'requester_review'  => 'Review hasil',
            default             => $step->action_label ?: 'Proses',
        };
    }

    // ── Status metadata ───────────────────────────────────────────────────────

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending'               => 'Pending',
            'accepted'              => 'Diterima',
            'rejected'              => 'Ditolak',
            'forwarded_ga'          => 'Diteruskan ke GA',
            'forwarded_qa'          => 'Diteruskan ke QA',
            'forwarded_maintenance' => 'Diteruskan ke MTC',
            'pending_parts'         => 'Menunggu Parts',
            'parts_ordered'         => 'PR Dibuat (QAD)',
            'parts_received'        => 'Parts Tiba',
            'assigned_group'        => 'Assigned to Group',
            'assigned_member'       => 'Dalam Pengerjaan',
            'completed'             => 'Selesai (Review)',
            'rework'                => 'Rework',
            'finished'              => 'Finished',
            'cancelled'             => 'Dibatalkan',
            default                 => ucfirst($status),
        };
    }

    /**
     * Status tone. One small vocabulary instead of a per-status rainbow:
     * gold = menunggu · steel/ink = sedang berjalan · flame = butuh aksi kamu
     * forest = beres · brick = bermasalah · neutral = ditutup
     */
    public static function statusColor(string $status): string
    {
        return match ($status) {
            'pending'               => 'tone-gold',
            'accepted'              => 'tone-steel',
            'rejected'              => 'tone-brick',
            'forwarded_ga',
            'forwarded_qa',
            'forwarded_maintenance' => 'tone-ink',
            'pending_parts'         => 'tone-gold',
            'parts_ordered'         => 'tone-steel',
            'parts_received'        => 'tone-forest',
            'assigned_group'        => 'tone-steel',
            'assigned_member'       => 'tone-ink',
            'completed'             => 'tone-flame',
            'rework'                => 'tone-brick',
            'finished'              => 'tone-forest',
            'cancelled'             => 'tone-neutral',
            default                 => 'tone-neutral',
        };
    }

    /** Matching dot colour, for dense lists where a chip is too heavy. */
    public static function statusDot(string $status): string
    {
        return 'tone-dot ' . str_replace('tone-', 'tone-dot-', self::statusColor($status));
    }

    public static function priorityColor(string $priority): string
    {
        return match ($priority) {
            'low'    => 'tone-neutral',
            'medium' => 'tone-steel',
            'high'   => 'tone-flame',
            'urgent' => 'tone-brick',
            default  => 'tone-neutral',
        };
    }
}
