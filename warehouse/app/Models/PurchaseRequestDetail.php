<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestDetail extends Model
{
    protected $fillable = [
        'pr_id', 'item_id', 'procurement_item_id', 'qty_needed', 'current_stock', 'min_stock', 'notes',
    ];

    protected $casts = [
        'qty_needed'    => 'float',
        'current_stock' => 'float',
        'min_stock'     => 'float',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class, 'pr_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function procurementItem()
    {
        return $this->belongsTo(ProcurementItem::class);
    }

    public function sources()
    {
        return $this->hasMany(PurchaseRequestDetailSource::class, 'pr_detail_id');
    }

    /**
     * Baris `min_stock` pakai item_id (generic Item), baris `ga_aggregation`
     * pakai procurement_item_id (katalog ProcurementItem) — accessor ini
     * menyamakan bentuknya biar view tidak perlu tahu bedanya.
     */
    public function resolvedItem(): object
    {
        if ($this->item_id) {
            return (object) [
                'code' => $this->item->item_code,
                'name' => $this->item->description,
                'uom'  => $this->item->unit,
            ];
        }

        return (object) [
            'code' => $this->procurementItem->item_code,
            'name' => $this->procurementItem->name,
            'uom'  => $this->procurementItem->uom->code,
        ];
    }
}
