<?php

namespace Tests\Unit;

use App\Models\QadItem;
use Tests\TestCase;

class QadItemExclusionConfigTest extends TestCase
{
    public function test_excluded_prod_lines_are_fg_rm_sa(): void
    {
        $this->assertSame(['FG', 'RM', 'SA'], QadItem::excludedProdLines());
    }

    public function test_is_excluded_is_case_insensitive_and_trimmed(): void
    {
        $this->assertTrue(QadItem::isExcludedProdLine('fg'));
        $this->assertTrue(QadItem::isExcludedProdLine(' RM '));
        $this->assertTrue(QadItem::isExcludedProdLine('sa'));
        $this->assertFalse(QadItem::isExcludedProdLine('OS'));
        $this->assertFalse(QadItem::isExcludedProdLine(''));
        $this->assertFalse(QadItem::isExcludedProdLine(null));
    }
}
