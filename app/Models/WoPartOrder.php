<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoPartOrder extends Model
{
    protected $fillable = [
        'wo_id', 'requested_by', 'handled_by',
        'pr_number', 'request_note', 'warehouse_note',
        'status', 'pr_date', 'expected_arrival', 'received_at', 'qad_response',
    ];

    protected $casts = [
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
        return $this->hasMany(WoPartOrderLine::class);
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
            'pending_warehouse' => 'bg-yellow-100 text-yellow-800',
            'pr_created'        => 'bg-blue-100 text-blue-800',
            'received'          => 'bg-green-100 text-green-800',
            default             => 'bg-gray-100 text-gray-600',
        };
    }
}
