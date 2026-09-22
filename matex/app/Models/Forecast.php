<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forecast extends Model
{
    protected $fillable = [
        'supplier_rm_id',
        'period_month',
        'file_path',
        'original_filename',
        'notes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
        ];
    }

    public function supplierRm(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_rm_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ForecastItem::class);
    }

    public function periodKey(): string
    {
        return $this->period_month?->format('Y-m') ?? '';
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }
}
