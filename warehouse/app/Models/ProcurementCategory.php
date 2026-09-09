<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementCategory extends Model
{
    protected $fillable = ['name', 'is_active', 'parent_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function items()
    {
        return $this->hasMany(ProcurementItem::class, 'category_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('name');
    }
}
