<?php

namespace App\Models;

use App\Enums\QadSyncStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receiving extends Model
{
    protected $fillable = [
        'delivery_note_id',
        'delivery_schedule_id',
        'received_qty',
        'received_by',
        'received_at',
        'qad_status',
        'qad_payload',
        'qad_response',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'received_qty' => 'integer',
            'received_at' => 'datetime',
            'qad_status' => QadSyncStatus::class,
            'qad_payload' => 'array',
            'qad_response' => 'array',
        ];
    }

    public function deliveryNote(): BelongsTo
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function deliverySchedule(): BelongsTo
    {
        return $this->belongsTo(DeliverySchedule::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
