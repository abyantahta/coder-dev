<?php

namespace App\Models;

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
        'accepted_at', 'assigned_group_at', 'deadline', 'parts_ready_at',
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
        'deadline'           => 'datetime',
        'parts_ready_at'     => 'datetime',
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

    public static function generateWoNumber(): string
    {
        $prefix = 'WO-' . now()->format('Ym') . '-';
        $last = static::where('wo_number', 'like', $prefix . '%')
            ->orderByDesc('wo_number')
            ->value('wo_number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
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
            && now()->isAfter($this->deadline)
            && ! in_array($this->status, ['finished', 'cancelled', 'rejected', 'forwarded_ga', 'forwarded_qa', 'forwarded_maintenance']);
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
