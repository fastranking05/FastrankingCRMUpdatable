<?php

namespace Tests\Unit\Support;

use App\Support\LeadDisplayId;
use PHPUnit\Framework\TestCase;

class LeadDisplayIdTest extends TestCase
{
    public function test_it_formats_lead_ids_with_prefix_and_padding(): void
    {
        $this->assertSame('FRLID0000001', LeadDisplayId::format(1));
        $this->assertSame('FRLID0000010', LeadDisplayId::format(10));
        $this->assertSame('FRLID0000198', LeadDisplayId::format(198));
    }

    public function test_it_parses_formatted_lead_ids(): void
    {
        $this->assertSame(1, LeadDisplayId::parse('FRLID0000001'));
        $this->assertSame(10, LeadDisplayId::parse('FRLID0000010'));
        $this->assertSame(198, LeadDisplayId::parse('FRLID0000198'));
        $this->assertSame(198, LeadDisplayId::parse('frlid0000198'));
        $this->assertSame(198, LeadDisplayId::parse('FRLID198'));
    }

    public function test_it_resolves_plain_numeric_ids(): void
    {
        $this->assertSame(198, LeadDisplayId::resolveNumericId('198'));
        $this->assertSame(198, LeadDisplayId::resolveNumericId('FRLID0000198'));
    }

    public function test_it_returns_null_for_invalid_values(): void
    {
        $this->assertNull(LeadDisplayId::parse('FRLID'));
        $this->assertNull(LeadDisplayId::parse('ABC123'));
        $this->assertNull(LeadDisplayId::resolveNumericId('ABC123'));
    }
}
