<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemStock extends Model
{
    public $timestamps = false;

    protected $fillable = ['item_id', 'warehouse', 'qty_on_hand', 'qty_reserved', 'last_sync_at'];

    protected $casts = [
        'qty_on_hand'   => 'float',
        'qty_reserved'  => 'float',
        'last_sync_at'  => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
