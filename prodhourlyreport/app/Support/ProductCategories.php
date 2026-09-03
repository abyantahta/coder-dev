<?php

namespace App\Support;

final class ProductCategories
{
    /** @return list<string> */
    public static function values(): array
    {
        return ['FG', 'SA', 'RM'];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (string $value) => ['value' => $value, 'label' => $value],
            self::values(),
        );
    }
}
