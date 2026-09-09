<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $fillable = [
        'transaction_id', 'item_id', 'procurement_request_detail_id', 'qty_requested', 'qty_approved', 'notes',
    ];

    protected $casts = [
        'qty_requested' => 'float',
        'qty_approved'  => 'float',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function procurementRequestDetail()
    {
        return $this->belongsTo(ProcurementRequestDetail::class);
    }
}
