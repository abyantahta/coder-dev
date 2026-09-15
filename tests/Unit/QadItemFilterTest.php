<?php

namespace Tests\Unit;

use App\Models\QadItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QadItemFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_hides_excluded_prod_lines_but_keeps_blank(): void
    {
        QadItem::create(['qad_code' => 'A-1', 'description' => 'Bolt', 'prod_line' => 'OS']);
        QadItem::create(['qad_code' => 'A-2', 'description' => 'FG item', 'prod_line' => 'FG']);
        QadItem::create(['qad_code' => 'A-3', 'description' => 'RM item', 'prod_line' => 'rm']);
        QadItem::create(['qad_code' => 'A-4', 'description' => 'Blank line', 'prod_line' => null]);
        QadItem::create(['qad_code' => 'A-5', 'description' => 'Empty line', 'prod_line' => '']);

        $codes = QadItem::withoutExcludedProdLines()->pluck('qad_code')->all();

        $this->assertEqualsCanonicalizing(['A-1', 'A-4', 'A-5'], $codes);
    }

    public function test_purge_deletes_excluded_prod_lines_only(): void
    {
        QadItem::create(['qad_code' => 'B-1', 'prod_line' => 'OS']);
        QadItem::create(['qad_code' => 'B-2', 'prod_line' => 'SA']);
        QadItem::create(['qad_code' => 'B-3', 'prod_line' => 'FG']);

        $deleted = QadItem::purgeExcludedProdLines();

        $this->assertSame(2, $deleted);
        $this->assertSame(['B-1'], QadItem::pluck('qad_code')->all());
    }

    public function test_search_matches_code_description_and_part_number(): void
    {
        QadItem::create(['qad_code' => 'SCR-01', 'description' => 'Screw M6', 'part_number' => 'PN-100']);
        QadItem::create(['qad_code' => 'NUT-01', 'description' => 'Hex nut', 'part_number' => 'PN-200']);

        $this->assertTrue(QadItem::search('SCR')->pluck('qad_code')->contains('SCR-01'));
        $this->assertTrue(QadItem::search('Hex')->pluck('qad_code')->contains('NUT-01'));
        $this->assertTrue(QadItem::search('PN-100')->pluck('qad_code')->contains('SCR-01'));
        $this->assertSame(2, QadItem::search(null)->count());
    }
}
