<?php

namespace App\Models;

use App\Enums\CompanyType;
use Illuminate\Database\Eloquent\Model;

class QadSupplier extends Model
{
    protected $fillable = [
        'qad_code', 'category', 'name', 'address_line1', 'address_line2', 'city',
        'country', 'contact_name', 'phone', 'email', 'is_active', 'last_synced_at',
    ];

    protected $casts = [
        'category' => CompanyType::class,
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategory($query, ?CompanyType $category)
    {
        return $query->where('category', $category?->value);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(fn ($q) => $q
            ->where('qad_code', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%")
            ->orWhere('city', 'like', "%{$term}%"));
    }
}
