<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestDetailSource extends Model
{
    protected $fillable = [
        'pr_detail_id', 'procurement_request_detail_id', 'department_id', 'qty',
    ];

    protected $casts = ['qty' => 'float'];

    public function prDetail()
    {
        return $this->belongsTo(PurchaseRequestDetail::class, 'pr_detail_id');
    }

    public function procurementRequestDetail()
    {
        return $this->belongsTo(ProcurementRequestDetail::class, 'procurement_request_detail_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
