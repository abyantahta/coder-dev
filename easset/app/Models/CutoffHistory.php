<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CutoffHistory extends Model
{
    /** @use HasFactory<\Database\Factories\CutoffHistoryFactory> */
    use HasFactory;
    protected $guarded = [];
    
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'cutoff_counter');
    }
}
