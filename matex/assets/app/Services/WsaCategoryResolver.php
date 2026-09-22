<?php

namespace App\Services;

class WsaCategoryResolver
{
    /**
     * Map a WSA `t_fa_facls_id` code to the category it should be filed
     * under, and an optional lifetime (in months) that overrides the
     * category's own `lifetime` column.
     *
     * @return array{categoryName: string, lifetimeOverride: ?int}
     */
    public function resolve(string $wsaCode): array
    {
        return match ($wsaCode) {
            'TOOLING', 'TOOLING2' => ['categoryName' => 'Tooling', 'lifetimeOverride' => null],
            'TOOLING3' => ['categoryName' => 'Tooling', 'lifetimeOverride' => 36],
            'BUILDING' => ['categoryName' => 'Building', 'lifetimeOverride' => null],
            'VEHICLE' => ['categoryName' => 'Vehicle', 'lifetimeOverride' => null],
            'OFC-EQP' => ['categoryName' => 'Office Equipment', 'lifetimeOverride' => null],
            'MACHINE' => ['categoryName' => 'Machine', 'lifetimeOverride' => null],
            default => ['categoryName' => 'Vehicle', 'lifetimeOverride' => null],
        };
    }
}
