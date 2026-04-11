<?php

namespace Tests\Unit;

use App\Models\Section;
use PHPUnit\Framework\TestCase;

class SectionTest extends TestCase
{
    public function test_display_name_returns_section_code_only(): void
    {
        $section = new Section([
            'section_code' => 'BSCS301A',
        ]);

        $this->assertSame('BSCS301A', $section->display_name);
    }
}
