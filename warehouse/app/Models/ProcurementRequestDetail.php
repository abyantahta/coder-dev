<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementRequestDetail extends Model
{
    protected $fillable = ['request_id', 'item_id', 'qty', 'uom_id', 'notes', 'is_over_budget'];
    protected $casts    = ['qty' => 'float', 'is_over_budget' => 'boolean'];

    public function request()      { return $this->belongsTo(ProcurementRequest::class, 'request_id'); }
    public function item()         { return $this->belongsTo(ProcurementItem::class, 'item_id'); }
    public function uom()          { return $this->belongsTo(Uom::class); }
    public function fulfillments() { return $this->hasMany(TransactionDetail::class, 'procurement_request_detail_id'); }

    /**
     * Sisa qty yang belum diambil lewat GA checkout — dipakai untuk validasi
     * agar serah-terima barang tidak melebihi qty yang diminta di awal.
     */
    public function remainingQty(): float
    {
        $taken = $this->fulfillments()
            ->whereHas('transaction', fn($q) => $q->where('status', 'completed'))
            ->sum('qty_approved');

        return max(0, (float) $this->qty - (float) $taken);
    }
}
