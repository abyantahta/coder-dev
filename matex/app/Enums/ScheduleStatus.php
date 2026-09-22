<?php

namespace App\Enums;

enum ScheduleStatus: string
{
    case Planned = 'planned';
    case ShipConfirmed = 'ship_confirmed';
    case OhpOk = 'ohp_ok';
    case Received = 'received';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Terjadwal',
            self::ShipConfirmed => 'Dikirim ke OHP',
            self::OhpOk => 'OK OHP',
            self::Received => 'Received',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'slate',
            self::ShipConfirmed => 'amber',
            self::OhpOk => 'blue',
            self::Received => 'emerald',
        };
    }

    /** DN sudah dikirim dan sudah di-approve OHP (atau sudah received PPIC). */
    public function isSentAndApproved(): bool
    {
        return $this === self::OhpOk || $this === self::Received;
    }

    public static function sentAndApprovedValues(): array
    {
        return [self::OhpOk->value, self::Received->value];
    }
}
