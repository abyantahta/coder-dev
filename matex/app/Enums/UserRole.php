<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Purchasing = 'purchasing';
    case SupplierRm = 'supplier_rm';
    case SupplierOhp = 'supplier_ohp';
    case Ppic = 'ppic';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Purchasing => 'Purchasing SDI',
            self::SupplierRm => 'Supplier Raw Material',
            self::SupplierOhp => 'Supplier OH Part',
            self::Ppic => 'PPIC SDI',
        };
    }
}
