<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDepartmentBudgetTopup extends Model
{
    protected $fillable = [
        'department_id', 'procurement_item_id', 'year', 'month', 'amount', 'notes', 'added_by',
    ];

    protected $casts = ['amount' => 'float'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function procurementItem()
    {
        return $this->belongsTo(ProcurementItem::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
