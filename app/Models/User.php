<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'role', 'department', 'unit_id', 'group_id',
        'department_id', 'dept_role_id', 'is_superadmin',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function unit(): BelongsTo
    {
        return $this->belongsTo(MaintenanceUnit::class, 'unit_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MaintenanceGroup::class, 'group_id');
    }

    public function dept(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function deptRole(): BelongsTo
    {
        return $this->belongsTo(DepartmentRole::class, 'dept_role_id');
    }

    public function submittedWorkOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'requester_id');
    }

    public function assignedWorkOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'assigned_member_id');
    }

    // ── Role helpers ──────────────────────────────────────────────────────────

    public function isSectionHead(): bool   { return $this->role === 'section_head'; }
    public function isUnitHead(): bool      { return $this->role === 'unit_head'; }
    public function isGroupHead(): bool     { return $this->role === 'group_head'; }
    public function isMember(): bool        { return $this->role === 'member'; }
    public function isWarehouseMtc(): bool  { return $this->role === 'warehouse_mtc'; }
    public function isQaGroupHead(): bool    { return $this->role === 'qa_group_head'; }
    public function isQaMember(): bool       { return $this->role === 'qa_member'; }
    public function isQaSectionHead(): bool  { return $this->role === 'qa_section_head'; }
    public function isQaStaff(): bool        { return in_array($this->role, ['qa_group_head', 'qa_member', 'qa_section_head']); }
    public function isGaSectionHead(): bool  { return $this->role === 'ga_section_head'; }
    public function isMaintenanceStaff(): bool
    {
        return in_array($this->role, ['section_head', 'unit_head', 'group_head', 'member']);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_superadmin;
    }

    public function isDeptSuperuser(): bool
    {
        return $this->deptRole?->is_superuser === true;
    }

    public function hasDeptRole(string $key): bool
    {
        return $this->deptRole?->key === $key;
    }

    public function isInDept(string $slug): bool
    {
        return $this->dept?->slug === $slug;
    }

    public static function roleLabel(string $role): string
    {
        return match ($role) {
            'section_head'  => 'Section Head',
            'unit_head'     => 'Unit Head',
            'group_head'    => 'Group Head',
            'member'        => 'Member',
            'warehouse_mtc' => 'Warehouse MTC',
            'qa_group_head'  => 'QA Group Head',
            'qa_member'      => 'QA Member',
            'qa_section_head' => 'QA Section Head',
            'ga_section_head' => 'GA Section Head',
            'user'          => 'User',
            default         => ucfirst($role),
        };
    }

    // ── Service Rate ──────────────────────────────────────────────────────────

    public function getServiceRateAttribute(): float
    {
        if ($this->role === 'member') {
            $finished = WorkOrder::where('assigned_member_id', $this->id)
                ->where('status', 'finished')
                ->where('destination', 'maintenance')
                ->get();
            if ($finished->isEmpty()) return 0;
            return round($finished->avg('score'), 1);
        }

        if ($this->role === 'qa_member') {
            $finished = WorkOrder::where('assigned_member_id', $this->id)
                ->where('status', 'finished')
                ->where('destination', 'qa')
                ->get();
            if ($finished->isEmpty()) return 0;
            return round($finished->avg('score'), 1);
        }

        if ($this->role === 'group_head') {
            $members = User::where('group_id', $this->group_id)->where('role', 'member')->get();
            if ($members->isEmpty()) return 0;
            return round($members->map(fn ($m) => $m->service_rate)->average(), 1);
        }

        if ($this->role === 'unit_head') {
            $members = User::where('unit_id', $this->unit_id)->where('role', 'member')->get();
            if ($members->isEmpty()) return 0;
            return round($members->map(fn ($m) => $m->service_rate)->average(), 1);
        }

        if ($this->role === 'qa_group_head' || $this->role === 'qa_section_head') {
            $members = User::where('role', 'qa_member')->where('department', $this->department)->get();
            if ($members->isEmpty()) return 0;
            return round($members->map(fn ($m) => $m->service_rate)->average(), 1);
        }

        return 0;
    }

    public function getWoCountAttribute(): int
    {
        if ($this->role === 'member') {
            return WorkOrder::where('assigned_member_id', $this->id)->count();
        }
        return 0;
    }

    public function getFinishedWoCountAttribute(): int
    {
        if ($this->role === 'member') {
            return WorkOrder::where('assigned_member_id', $this->id)->where('status', 'finished')->count();
        }
        return 0;
    }
}
