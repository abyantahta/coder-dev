<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDepartmentBudget extends Model
{
    protected $fillable = ['department_id', 'procurement_item_id', 'default_budget'];

    protected $casts = ['default_budget' => 'float'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function procurementItem()
    {
        return $this->belongsTo(ProcurementItem::class);
    }
}
