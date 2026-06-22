<?php

namespace Tests\Unit\Models;

use App\Models\GlobalSearchDocument;
use PHPUnit\Framework\TestCase;

class GlobalSearchDocumentTest extends TestCase
{
    public function test_it_truncates_long_title_values_for_storage(): void
    {
        $longTitle = str_repeat('A', 300);

        $this->assertSame(
            str_repeat('A', GlobalSearchDocument::TITLE_MAX_LENGTH),
            GlobalSearchDocument::truncateString($longTitle, GlobalSearchDocument::TITLE_MAX_LENGTH)
        );
    }

    public function test_it_leaves_short_values_unchanged(): void
    {
        $this->assertSame('FRLID0000198', GlobalSearchDocument::truncateString('FRLID0000198', 255));
        $this->assertNull(GlobalSearchDocument::truncateString(null, 255));
    }
}
