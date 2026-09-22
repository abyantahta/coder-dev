<?php

namespace App\Models;

use App\Enums\ScheduleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliverySchedule extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'purchase_order_item_id',
        'ohp_supplier_id',
        'rm_sj_number',
        'scheduled_date',
        'qty',
        'qty_confirmed',
        'status',
        'ship_confirmed_at',
        'ship_confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ScheduleStatus::class,
            'scheduled_date' => 'date',
            'qty' => 'integer',
            'qty_confirmed' => 'integer',
            'ship_confirmed_at' => 'datetime',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function ohpSupplier(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'ohp_supplier_id');
    }

    public function shipConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ship_confirmed_by');
    }

    public function deliveryNote(): HasOne
    {
        return $this->hasOne(DeliveryNote::class);
    }

    public function receivings(): HasMany
    {
        return $this->hasMany(Receiving::class);
    }

    public function receiving(): HasOne
    {
        return $this->hasOne(Receiving::class)->latestOfMany();
    }

    public function effectiveQty(): string
    {
        return (string) ($this->qty_confirmed ?? $this->qty);
    }
}
