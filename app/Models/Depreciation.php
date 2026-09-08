<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depreciation extends Model
{
    /** @use HasFactory<\Database\Factories\DepreciationFactory> */
    use HasFactory;
    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id','id');
    }
}
