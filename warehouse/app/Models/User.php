<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    const ROLES = ['superadmin', 'admin', 'section', 'manager', 'director', 'purchasing', 'ga', 'user'];

    protected $fillable = [
        'npk', 'name', 'email', 'phone', 'password',
        'role', 'department_id', 'photo', 'qr_code', 'is_active', 'qad_approver_code', 'qad_password',
        'qad_route_to_apr', 'qad_route_to_buyer', 'qad_requested_by', 'qad_end_user_id',
    ];

    protected $hidden = ['password', 'remember_token', 'qad_password'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'qad_password'      => 'encrypted',
        ];
    }

    public function department()       { return $this->belongsTo(Department::class); }
    public function transactions()     { return $this->hasMany(Transaction::class); }
    public function purchaseRequests() { return $this->hasMany(PurchaseRequest::class, 'created_by'); }
    public function procurementRequests() { return $this->hasMany(ProcurementRequest::class); }

    /** Departemen TAMBAHAN yang boleh diapprove user ini (di luar department_id sendiri) — kasus kasi rangkap jabatan. */
    public function approverDepartments()
    {
        return $this->belongsToMany(Department::class, 'department_section_approvers');
    }

    /** Semua departemen yang boleh diapprove user section ini (department_id sendiri + tambahan). */
    public function sectionApprovalDepartmentIds(): array
    {
        return collect([$this->department_id])
            ->merge($this->approverDepartments->pluck('id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function canApproveSectionFor(?int $departmentId): bool
    {
        return $departmentId && in_array($departmentId, $this->sectionApprovalDepartmentIds());
    }

    // ===== ROLE CHECKS =====
    public function isSuperAdmin(): bool  { return $this->role === 'superadmin'; }
    public function isAdmin(): bool       { return in_array($this->role, ['admin', 'superadmin']); }
    public function isSection(): bool     { return $this->role === 'section'; }
    public function isManager(): bool     { return $this->role === 'manager'; }
    public function isDirector(): bool    { return $this->role === 'director'; }
    public function isPurchasing(): bool  { return $this->role === 'purchasing'; }
    public function isGa(): bool          { return $this->role === 'ga'; }
    public function isUser(): bool        { return $this->role === 'user'; }

    /**
     * Kasar/UI-only: dipakai untuk tampil-tidaknya menu, BUKAN untuk otorisasi approval.
     * Otorisasi approval per-record ada di ProcurementRequest::canBeApprovedBy() (dept-scoped).
     */
    public function canApproveSection(): bool  { return in_array($this->role, ['section', 'superadmin']); }
    public function canApproveDirector(): bool { return in_array($this->role, ['director', 'superadmin']); }

    public function hasWarehouseAccess(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    public function getRoleLabel(): string
    {
        return match($this->role) {
            'superadmin' => 'Super Admin',
            'admin'      => 'Admin Warehouse',
            'section'    => 'Section / Kasi',
            'manager'    => 'Manager',
            'director'   => 'Direktur',
            'purchasing' => 'Purchasing',
            'ga'         => 'General Affair',
            default      => 'User',
        };
    }

    public function getRoleBadge(): string
    {
        return match($this->role) {
            'superadmin' => 'danger',
            'admin'      => 'warning',
            'section'    => 'info',
            'manager'    => 'primary',
            'director'   => 'success',
            'purchasing' => 'dark',
            'ga'         => 'info',
            default      => 'secondary',
        };
    }
}
