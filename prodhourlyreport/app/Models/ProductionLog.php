<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['line_id', 'product_model_id', 'product_id', 'user_id', 'client_uuid', 'logged_at', 'total_production', 'total_reject', 'total_repair', 'notes'])]
class ProductionLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
            'total_production' => 'integer',
            'total_reject' => 'integer',
            'total_repair' => 'integer',
        ];
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(Line::class);
    }

    public function productModel(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
