<?php

namespace App\Actions\Concerns;

use App\Models\Item;
use App\Models\QadItem;

trait ResolvesQadItem
{
    /**
     * Find or create the local Item record for a QAD item master code.
     * item_number is the shared key between `items` and `qad_items` (the
     * QAD item master is now the source of truth for the PO item picker).
     * An existing item's manually-set price/subcont_ohp_id is left alone —
     * only description/uom are seeded from QAD the first time a code is used.
     */
    private function resolveItemFromQadCode(string $qadCode): Item
    {
        $existing = Item::where('item_number', $qadCode)->first();

        if ($existing) {
            return $existing;
        }

        $qadItem = QadItem::where('qad_code', $qadCode)->first();

        return Item::create([
            'item_number' => $qadCode,
            'description' => $qadItem?->description ?: $qadCode,
            'uom' => $qadItem?->uom ?: 'kg',
            'is_active' => true,
        ]);
    }
}
