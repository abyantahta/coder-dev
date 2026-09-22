<?php

namespace App\Models;

use App\Enums\PoStatus;
use App\Enums\QadSyncStatus;
use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number',
        'supplier_rm_id',
        'due_date',
        'status',
        'created_by',
        'notes',
        'rejection_reason',
        'submitted_at',
        'rm_confirmed_at',
        'purchasing_approved_at',
        'qad_status',
        'qad_po_number',
        'qad_payload',
        'qad_response',
    ];

    protected function casts(): array
    {
        return [
            'status' => PoStatus::class,
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'rm_confirmed_at' => 'datetime',
            'purchasing_approved_at' => 'datetime',
            'qad_status' => QadSyncStatus::class,
            'qad_payload' => 'array',
            'qad_response' => 'array',
        ];
    }

    public function supplierRm(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_rm_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DeliverySchedule::class);
    }

    public function deliveryNotes(): HasMany
    {
        return $this->hasMany(DeliveryNote::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PoStatusLog::class)->latest();
    }

    public function isEditable(): bool
    {
        return $this->status === PoStatus::Draft;
    }

    /**
     * Supplier RM hanya melihat PO miliknya; Supplier OHP hanya PO yang
     * memiliki jadwal pengiriman ke company mereka.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->when(
                $user->hasRole(UserRole::SupplierRm),
                fn (Builder $q) => $q->where('supplier_rm_id', $user->company_id)
            )
            ->when(
                $user->hasRole(UserRole::SupplierOhp),
                fn (Builder $q) => $q->whereHas(
                    'schedules',
                    fn (Builder $sq) => $sq->where('ohp_supplier_id', $user->company_id)
                )
            );
    }

    /**
     * OHP hanya memuat jadwal (dan nama OHP) yang ditujukan ke mereka.
     */
    public function scopeWithSchedulesVisibleTo(Builder $query, User $user): Builder
    {
        return $query->with([
            'schedules' => function ($q) use ($user) {
                if ($user->hasRole(UserRole::SupplierOhp) && $user->company_id) {
                    $q->where('ohp_supplier_id', $user->company_id);
                }
            },
            'schedules.ohpSupplier',
        ]);
    }

    public function scopeWithFulfillmentCounts(Builder $query): Builder
    {
        return $query->withCount([
            'schedules',
            'schedules as unapproved_schedules_count' => fn (Builder $q) => $q->whereNotIn(
                'status',
                ScheduleStatus::sentAndApprovedValues()
            ),
        ]);
    }

    /**
     * Closed = seluruh jadwal/DN sudah dikirim dan sudah di-approve OHP.
     */
    public function getIsClosedAttribute(): bool
    {
        if (array_key_exists('schedules_count', $this->attributes)
            && array_key_exists('unapproved_schedules_count', $this->attributes)) {
            return (int) $this->schedules_count > 0
                && (int) $this->unapproved_schedules_count === 0;
        }

        if (! $this->schedules()->exists()) {
            return false;
        }

        return ! $this->schedules()
            ->whereNotIn('status', ScheduleStatus::sentAndApprovedValues())
            ->exists();
    }

    public function restrictRelationsFor(User $user): static
    {
        if (! $user->hasRole(UserRole::SupplierOhp) || ! $user->company_id) {
            return $this;
        }

        $ohpId = (int) $user->company_id;

        if ($this->relationLoaded('schedules')) {
            $this->setRelation(
                'schedules',
                $this->schedules
                    ->filter(fn (DeliverySchedule $schedule) => (int) $schedule->ohp_supplier_id === $ohpId)
                    ->values()
            );
        }

        if ($this->relationLoaded('deliveryNotes')) {
            $this->setRelation(
                'deliveryNotes',
                $this->deliveryNotes
                    ->filter(fn (DeliveryNote $dn) => (int) $dn->deliverySchedule?->ohp_supplier_id === $ohpId)
                    ->values()
            );
        }

        if ($this->relationLoaded('items') && $this->relationLoaded('schedules')) {
            $itemIds = $this->schedules->pluck('purchase_order_item_id');
            $this->setRelation(
                'items',
                $this->items->whereIn('id', $itemIds)->values()
            );
        }

        return $this;
    }
}
