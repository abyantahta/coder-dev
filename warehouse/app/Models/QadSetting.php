<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row config (selalu id=1). Kosong = pakai default dari .env.
 */
class QadSetting extends Model
{
    protected $fillable = ['qad_soap_url', 'qad_wsa_url'];

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
