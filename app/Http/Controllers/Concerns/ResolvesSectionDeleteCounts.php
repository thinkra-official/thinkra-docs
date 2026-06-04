<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Lesson;
use App\Models\Section;
use App\Models\SubSection;

trait ResolvesSectionDeleteCounts
{
    protected function sectionDeleteSummary(Section $section): array
    {
        $subSections = $section->subSections()->count();
        $directLessons = $section->directLessons()->count();
        $subLessons = Lesson::query()
            ->whereHas('subSection', fn ($q) => $q->where('section_id', $section->id))
            ->count();

        return [
            'subSections' => $subSections,
            'lessons' => $directLessons + $subLessons,
            'directLessons' => $directLessons,
            'subLessons' => $subLessons,
        ];
    }

    protected function subSectionDeleteSummary(SubSection $subSection): int
    {
        return $subSection->lessons()->count();
    }
}
