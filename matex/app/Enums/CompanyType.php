<?php

namespace App\Enums;

enum CompanyType: string
{
    case Sdi = 'sdi';
    case RawMat = 'raw_mat';
    case Ohp = 'ohp';

    public function label(): string
    {
        return match ($this) {
            self::Sdi => 'PT. SDI',
            self::RawMat => 'Supplier Raw Material',
            self::Ohp => 'Supplier OH Part',
        };
    }
}
