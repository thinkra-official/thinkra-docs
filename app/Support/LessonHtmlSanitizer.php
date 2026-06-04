<?php

namespace App\Support;

final class LessonHtmlSanitizer
{
    /**
     * Normalize pasted/saved lesson HTML for RTL display and safe storage.
     */
    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = trim($html);
        if ($html === '') {
            return null;
        }

        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html) ?? $html;
        $html = preg_replace('/\s(on\w+|javascript:)=["\'][^"\']*["\']/i', '', $html) ?? $html;

        return $html;
    }
}
