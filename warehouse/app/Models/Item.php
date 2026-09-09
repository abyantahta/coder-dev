<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'item_code', 'description', 'unit', 'category',
        'warehouse', 'price', 'currency', 'photo', 'is_memo', 'is_active', 'last_sync_at',
    ];

    protected $casts = [
        'is_memo'      => 'boolean',
        'is_active'    => 'boolean',
        'last_sync_at' => 'datetime',
        'price'        => 'float',
    ];

    public function getInventoryValue(): float
    {
        return $this->getQtyOnHand() * $this->price;
    }

    public function stock()
    {
        return $this->hasOne(ItemStock::class);
    }

    public function memoStock()
    {
        return $this->hasOne(MemoStock::class);
    }

    public function minimumStock()
    {
        return $this->hasOne(MinimumStock::class);
    }

    public function departmentStocks()
    {
        return $this->hasMany(DepartmentStock::class);
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    /**
     * Stok item non-memo sekarang milik departemen masing-masing (department_stocks),
     * bukan lagi satu pool gudang pusat (ItemStock tetap ada sebagai cermin fisik/QAD sync).
     */
    public function getQtyOnHand(): float
    {
        if ($this->is_memo) {
            return $this->memoStock?->qty_on_hand ?? 0;
        }
        if ($this->relationLoaded('departmentStocks')) {
            return (float) $this->departmentStocks->sum('qty_held');
        }
        return (float) $this->departmentStocks()->sum('qty_held');
    }

    public function isBelowMinimum(): bool
    {
        $minStock = $this->minimumStock;
        if (!$minStock || !$minStock->is_active) return false;
        return $this->getQtyOnHand() <= $minStock->min_qty;
    }
}
