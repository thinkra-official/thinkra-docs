<?php

namespace Tests\Unit;

use App\Support\LessonHtmlSanitizer;
use PHPUnit\Framework\TestCase;

class LessonHtmlSanitizerTest extends TestCase
{
    public function test_strips_script_tags(): void
    {
        $html = '<p>مرحبا</p><script>alert(1)</script>';
        $clean = LessonHtmlSanitizer::clean($html);

        $this->assertStringNotContainsString('script', $clean ?? '');
        $this->assertStringContainsString('مرحبا', $clean ?? '');
    }

    public function test_empty_returns_null(): void
    {
        $this->assertNull(LessonHtmlSanitizer::clean(''));
        $this->assertNull(LessonHtmlSanitizer::clean(null));
    }
}
