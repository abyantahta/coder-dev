<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoSparePart extends Model
{
    protected $fillable = [
        'wo_id', 'part_number', 'part_name', 'quantity', 'unit', 'is_available', 'notes',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'quantity' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id');
    }
}
