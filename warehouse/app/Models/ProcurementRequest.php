<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementRequest extends Model
{
    protected $fillable = [
        'req_no', 'user_id', 'department_id', 'purpose', 'status', 'notes',
        'section_by', 'section_at', 'section_notes',
        'ga_by', 'ga_at', 'ga_notes',
        'aggregated_into_pr_id', 'aggregated_at',
        'rejected_stage', 'reject_reason', 'req_date',
    ];

    protected $casts = [
        'section_at'    => 'datetime',
        'ga_at'         => 'datetime',
        'aggregated_at' => 'datetime',
        'req_date'      => 'date',
    ];

    public function user()         { return $this->belongsTo(User::class); }
    public function department()   { return $this->belongsTo(Department::class); }
    public function details()      { return $this->hasMany(ProcurementRequestDetail::class, 'request_id'); }
    public function sectionBy()    { return $this->belongsTo(User::class, 'section_by'); }
    public function gaBy()         { return $this->belongsTo(User::class, 'ga_by'); }
    public function aggregatedPr() { return $this->belongsTo(PurchaseRequest::class, 'aggregated_into_pr_id'); }
    public function attachments()  { return $this->hasMany(ProcurementRequestAttachment::class, 'request_id'); }

    /**
     * Kalau sudah diagregasi GA, status yang ditampilkan ikut progres asli
     * PurchaseRequest hasil agregasinya (bukan cuma "Sudah Diproses GA" terus
     * walau sebenarnya sudah disetujui Direktur / barang sudah diterima).
     */
    public function getStatusLabel(): string
    {
        if ($this->status === 'aggregated' && $this->aggregatedPr) {
            return match($this->aggregatedPr->status) {
                'sent_to_qad'        => 'Terkirim ke QAD',
                'send_failed'        => 'Gagal Kirim ke QAD',
                'director_confirmed'  => 'Disetujui Direktur',
                'director_denied'     => 'Ditolak Direktur (Direvisi GA)',
                'received'            => 'Barang Diterima',
                'partially_received'  => 'Diterima Sebagian',
                'distributing'        => 'Sedang Didistribusikan',
                'completed'           => 'Selesai',
                default               => 'Sudah Diproses GA',
            };
        }

        return match($this->status) {
            'draft'           => 'Draft',
            'pending_section' => 'Menunggu Section/Dept Head',
            'pending_ga'      => 'Menunggu Review GA',
            'aggregated'      => 'Sudah Diproses GA',
            'rejected'        => 'Ditolak',
            default           => $this->status,
        };
    }

    public function getStatusBadge(): string
    {
        if ($this->status === 'aggregated' && $this->aggregatedPr) {
            return match($this->aggregatedPr->status) {
                'sent_to_qad'        => 'primary',
                'send_failed'        => 'danger',
                'director_confirmed'  => 'success',
                'director_denied'     => 'danger',
                'received'            => 'info',
                'partially_received'  => 'warning',
                'distributing'        => 'info',
                'completed'           => 'success',
                default               => 'success',
            };
        }

        return match($this->status) {
            'draft'           => 'secondary',
            'pending_section' => 'warning',
            'pending_ga'      => 'warning',
            'aggregated'      => 'success',
            'rejected'        => 'danger',
            default           => 'secondary',
        };
    }

    public function getCurrentApprovalStage(): ?string
    {
        return match($this->status) {
            'pending_section' => 'section',
            'pending_ga'      => 'ga',
            default           => null,
        };
    }

    /**
     * Otorisasi approval per-record. Section HARUS berada di departemen yang sama
     * dengan request (dept head hanya boleh approve request departemennya sendiri).
     */
    public function canBeApprovedBy(User $user): bool
    {
        return match($this->status) {
            'pending_section' => $user->isSuperAdmin()
                || ($user->role === 'section' && $user->canApproveSectionFor($this->department_id)),
            'pending_ga' => $user->isSuperAdmin() || $user->isGa(),
            default      => false,
        };
    }

    /**
     * Timeline untuk ditampilkan di halaman detail. Tahap Director/QAD/PO
     * ada di timeline PurchaseRequest hasil agregasi (lihat aggregatedPr()).
     */
    /**
     * Timeline lengkap termasuk tahap yang terjadi di PurchaseRequest hasil
     * agregasi GA (director/po/receipt) — makanya field-nya diambil dari
     * $this->aggregatedPr, bukan dari ProcurementRequest ini sendiri.
     */
    public function getTimeline(): array
    {
        $agg = $this->aggregatedPr;

        $steps = [
            [
                'key'   => 'created',
                'label' => 'Permintaan Dibuat',
                'icon'  => 'bi-file-earmark-plus',
                'done'  => true,
                'rejected' => false,
                'by'    => $this->user->name,
                'at'    => $this->created_at,
                'notes' => $this->purpose,
            ],
            [
                'key'      => 'section',
                'label'    => 'Persetujuan Section / Dept Head',
                'icon'     => 'bi-person-check',
                'done'     => !is_null($this->section_at),
                'rejected' => $this->status === 'rejected' && $this->rejected_stage === 'section',
                'by'       => $this->sectionBy?->name,
                'at'       => $this->section_at,
                'notes'    => $this->section_notes,
            ],
            [
                'key'      => 'ga',
                'label'    => 'Review & Agregasi General Affair',
                'icon'     => 'bi-person-badge',
                'done'     => !is_null($this->ga_at),
                'rejected' => $this->status === 'rejected' && $this->rejected_stage === 'ga',
                'by'       => $this->gaBy?->name,
                'at'       => $this->ga_at,
                'notes'    => $this->ga_notes,
            ],
            [
                'key'      => 'director',
                'label'    => 'Persetujuan Direktur',
                'icon'     => 'bi-person-workspace',
                'done'     => $agg && !is_null($agg->director_at) && $agg->status !== 'director_denied',
                'rejected' => $agg && $agg->status === 'director_denied',
                'by'       => $agg?->directorBy?->name,
                'at'       => $agg?->director_at,
                'notes'    => $agg && $agg->status === 'director_denied' ? $agg->reject_reason : $agg?->director_notes,
            ],
            [
                'key'      => 'po',
                'label'    => 'PO Diterbitkan (QAD)',
                'icon'     => 'bi-receipt',
                'done'     => $agg && !empty($agg->qad_po_no),
                'rejected' => false,
                'by'       => null,
                'at'       => null,
                'notes'    => $agg?->qad_po_no ? "No. PO: {$agg->qad_po_no}" : null,
            ],
            [
                'key'      => 'receipt',
                'label'    => 'Barang Diterima',
                'icon'     => 'bi-box-seam',
                'done'     => $agg && in_array($agg->status, ['received', 'distributing', 'completed']),
                'rejected' => false,
                'by'       => $agg?->receivedBy?->name,
                'at'       => $agg?->received_at,
                'notes'    => null,
            ],
        ];

        // Tandai satu step "active" (sedang berjalan) — step pertama yang
        // belum done & belum rejected, selama belum ada step lebih awal yang
        // reject (kalau sudah reject, alurnya berhenti, tidak ada yang aktif).
        $blocked = false;
        foreach ($steps as &$step) {
            $step['active'] = false;
            if ($blocked) continue;
            if (!empty($step['rejected'])) { $blocked = true; continue; }
            if (!$step['done']) { $step['active'] = true; $blocked = true; }
        }
        unset($step);

        return $steps;
    }

    public static function generateReqNo(): string
    {
        $date = now()->format('Ymd');
        $last = self::whereDate('created_at', today())->count() + 1;
        return 'PR-ATK-' . $date . '-' . str_pad($last, 3, '0', STR_PAD_LEFT);
    }
}
