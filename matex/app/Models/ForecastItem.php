<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForecastItem extends Model
{
    protected $fillable = [
        'forecast_id',
        'item_id',
        'item_number',
        'description',
        'qty',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
        ];
    }

    public function forecast(): BelongsTo
    {
        return $this->belongsTo(Forecast::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
