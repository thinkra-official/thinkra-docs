<?php

namespace App\Enums;

enum LessonStatus: string
{
    case Draft = 'DRAFT';
    case NeedsReview = 'NEEDS_REVIEW';
    case Ready = 'READY';
    case Published = 'PUBLISHED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::NeedsReview => 'يحتاج مراجعة',
            self::Ready => 'جاهز',
            self::Published => 'منشور',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-700',
            self::NeedsReview => 'bg-amber-100 text-amber-800',
            self::Ready => 'bg-blue-100 text-blue-800',
            self::Published => 'bg-emerald-100 text-emerald-800',
        };
    }
}
