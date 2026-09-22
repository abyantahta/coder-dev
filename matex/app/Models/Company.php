<?php

namespace App\Models;

use App\Enums\CompanyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => CompanyType::class,
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function defaultItems(): HasMany
    {
        return $this->hasMany(Item::class, 'subcont_ohp_id');
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class, 'supplier_rm_id');
    }

    public function scopeRawMat($query)
    {
        return $query->where('type', CompanyType::RawMat);
    }

    public function scopeOhp($query)
    {
        return $query->where('type', CompanyType::Ohp);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
