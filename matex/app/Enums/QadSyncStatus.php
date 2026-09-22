<?php

namespace App\Enums;

enum QadSyncStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Success => 'Sukses',
            self::Failed => 'Gagal',
        };
    }
}
