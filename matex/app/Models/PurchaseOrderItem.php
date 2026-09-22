<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'qty_ordered',
        'qty_confirmed',
        'qad_line_number',
    ];

    protected function casts(): array
    {
        return [
            'qty_ordered' => 'integer',
            'qty_confirmed' => 'integer',
            'qad_line_number' => 'integer',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DeliverySchedule::class);
    }

    public function effectiveQty(): string
    {
        return (string) ($this->qty_confirmed ?? $this->qty_ordered);
    }
}
