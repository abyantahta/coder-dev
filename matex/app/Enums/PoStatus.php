<?php

namespace App\Enums;

enum PoStatus: string
{
    case Draft = 'draft';
    case AwaitingRmConfirm = 'awaiting_rm_confirm';
    case AwaitingPurchasingOk = 'awaiting_purchasing_ok';
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::AwaitingRmConfirm => 'Menunggu Konfirmasi RM',
            self::AwaitingPurchasingOk => 'Menunggu OK Purchasing',
            self::Confirmed => 'Confirmed',
            self::InProgress => 'Dalam Proses',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::AwaitingRmConfirm => 'amber',
            self::AwaitingPurchasingOk => 'orange',
            self::Confirmed => 'blue',
            self::InProgress => 'indigo',
            self::Completed => 'emerald',
            self::Cancelled => 'rose',
        };
    }
}
