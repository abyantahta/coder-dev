<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemoStock extends Model
{
    public $timestamps = false;

    protected $fillable = ['item_id', 'qty_on_hand', 'last_updated'];

    protected $casts = [
        'qty_on_hand'  => 'float',
        'last_updated' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
