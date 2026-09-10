<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoPartOrderLine extends Model
{
    protected $fillable = [
        'wo_part_order_id', 'qad_item_id', 'part_code', 'description',
        'quantity', 'uom', 'is_custom', 'added_by', 'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_custom' => 'boolean',
    ];

    public function partOrder(): BelongsTo
    {
        return $this->belongsTo(WoPartOrder::class, 'wo_part_order_id');
    }

    public function qadItem(): BelongsTo
    {
        return $this->belongsTo(QadItem::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
