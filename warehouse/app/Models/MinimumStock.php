<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MinimumStock extends Model
{
    protected $fillable = ['item_id', 'warehouse', 'min_qty', 'is_active'];

    protected $casts = [
        'min_qty'   => 'float',
        'is_active' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
