<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentStock extends Model
{
    protected $fillable = ['item_id', 'department_id', 'qty_held'];

    protected $casts = ['qty_held' => 'float'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
