<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoPartOrder extends Model
{
    protected $fillable = [
        'wo_id', 'requested_by', 'handled_by',
        'pr_number', 'qad_po_no', 'qad_approval_status', 'request_note', 'need_date', 'warehouse_note',
        'status', 'pr_date', 'expected_arrival', 'received_at', 'qad_response',
    ];

    protected $casts = [
        'need_date' => 'date',
        'pr_date' => 'date',
        'expected_arrival' => 'date',
        'received_at' => 'datetime',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function lines(): HasMany
    {
        // Explicit order — QAD line numbers (used both when creating the PR
        // and when receiving against it) are positional, so this ordering
        // must stay stable and match on both sides.
        return $this->hasMany(WoPartOrderLine::class)->orderBy('id');
    }

    public function isOverdue(): bool
    {
        return $this->expected_arrival
            && now()->isAfter($this->expected_arrival)
            && $this->status !== 'received';
    }

    public function getProcurementDaysAttribute(): ?int
    {
        if (! $this->pr_date || ! $this->received_at) {
            return null;
        }
        return $this->pr_date->diffInDays($this->received_at->toDateString());
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending_warehouse' => 'Menunggu Warehouse',
            'pr_created'        => 'PR Dibuat (QAD)',
            'received'          => 'Barang Diterima',
            default             => ucfirst($status),
        };
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'pending_warehouse' => 'tone-gold',
            'pr_created'        => 'tone-steel',
            'received'          => 'tone-forest',
            default             => 'tone-neutral',
        };
    }

    /**
     * QAD approval status. Primary signal is QAD's own ApprovalStatus code
     * ('2' confirmed live = approved) from SDI_getPRtoPO_ — see
     * App\Services\Qad\QadRequisitionService::findPurchaseOrder() and the
     * qad:sync-po-numbers command. Falls back to "does a PO exist yet" for
     * older rows synced before this field was captured.
     */
    public function isApprovedInQad(): bool
    {
        if ($this->qad_approval_status !== null) {
            return $this->qad_approval_status === '2';
        }

        return ! empty($this->qad_po_no);
    }

    public function qadApprovalLabel(): string
    {
        if (! $this->pr_number) {
            return 'Belum Dikirim ke QAD';
        }

        if (! $this->isApprovedInQad()) {
            return 'Menunggu Approval';
        }

        return $this->qad_po_no
            ? "Disetujui — PO {$this->qad_po_no}"
            : 'Disetujui — menunggu No. PO';
    }
}
