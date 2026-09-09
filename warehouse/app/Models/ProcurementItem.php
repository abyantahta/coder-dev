<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementItem extends Model
{
    protected $fillable = [
        'item_code', 'name', 'uom_id', 'category_id', 'description', 'photo', 'price', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean', 'price' => 'float'];

    public function uom()      { return $this->belongsTo(Uom::class); }
    public function category() { return $this->belongsTo(ProcurementCategory::class, 'category_id'); }
    public function details()  { return $this->hasMany(ProcurementRequestDetail::class, 'item_id'); }

    /**
     * Sequence otomatis per-prefix — "ATK-001", "OEQ-001", dst, masing-masing
     * prefix punya urutannya sendiri (bukan satu urutan gabungan).
     */
    public static function generateItemCode(string $prefix): string
    {
        $prefix = strtoupper($prefix);
        $substringStart = strlen($prefix) + 2; // +1 buat "-", +1 karena SUBSTRING 1-indexed

        $lastNumber = static::where('item_code', 'like', $prefix . '-%')
            ->selectRaw("MAX(CAST(SUBSTRING(item_code, {$substringStart}) AS UNSIGNED)) as max_num")
            ->value('max_num');

        return $prefix . '-' . str_pad((int) $lastNumber + 1, 3, '0', STR_PAD_LEFT);
    }
}
