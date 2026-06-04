<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\SubSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CourseStructureReorderService
{
    /**
     * @param  array{sections: array<int, array<string, mixed>>}  $payload
     */
    public function reorder(Course $course, array $payload): void
    {
        $course->load(['sections.directLessons', 'sections.subSections.lessons']);

        $sectionsById = $course->sections->keyBy('id');
        $subSectionsById = $course->sections
            ->flatMap(fn (Section $section) => $section->subSections)
            ->keyBy('id');

        $lessonsById = $this->lessonsForCourse($course);

        $this->assertPayloadMatchesCourse($sectionsById, $subSectionsById, $lessonsById, $payload['sections']);

        DB::transaction(function () use ($payload, $sectionsById, $subSectionsById, $lessonsById) {
            foreach ($payload['sections'] as $sectionData) {
                $section = $sectionsById->get((int) $sectionData['id']);
                if (! $section) {
                    abort(404);
                }

                $section->update(['sort_order' => (int) $sectionData['position']]);

                $seenLessonIds = [];

                foreach ($sectionData['lessons'] ?? [] as $lessonData) {
                    $lesson = $this->lessonOrFail($lessonsById, (int) $lessonData['id']);
                    $this->assertLessonNotDuplicated($seenLessonIds, $lesson->id);
                    $this->placeDirectLesson($lesson, $section, (int) $lessonData['position']);
                }

                foreach ($sectionData['subSections'] ?? [] as $subSectionData) {
                    $subSection = $subSectionsById->get((int) $subSectionData['id']);
                    if (! $subSection || (int) $subSection->section_id !== (int) $section->id) {
                        abort(404);
                    }

                    $subSection->update(['sort_order' => (int) $subSectionData['position']]);

                    foreach ($subSectionData['lessons'] ?? [] as $lessonData) {
                        $lesson = $this->lessonOrFail($lessonsById, (int) $lessonData['id']);
                        $this->assertLessonNotDuplicated($seenLessonIds, $lesson->id);
                        $this->placeSubSectionLesson($lesson, $subSection, (int) $lessonData['position']);
                    }
                }
            }
        });
    }

    /**
     * @return Collection<int, Lesson>
     */
    protected function lessonsForCourse(Course $course): Collection
    {
        return Lesson::query()
            ->where(function ($query) use ($course) {
                $query->whereHas('section', fn ($q) => $q->where('course_id', $course->id))
                    ->orWhereHas('subSection.section', fn ($q) => $q->where('course_id', $course->id));
            })
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  Collection<int, Section>  $sectionsById
     * @param  Collection<int, SubSection>  $subSectionsById
     * @param  Collection<int, Lesson>  $lessonsById
     * @param  array<int, array<string, mixed>>  $sectionsPayload
     */
    protected function assertPayloadMatchesCourse(
        Collection $sectionsById,
        Collection $subSectionsById,
        Collection $lessonsById,
        array $sectionsPayload
    ): void {
        $payloadSectionIds = collect($sectionsPayload)->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $courseSectionIds = $sectionsById->keys()->sort()->values();

        if ($payloadSectionIds->toArray() !== $courseSectionIds->toArray()) {
            abort(422, 'قائمة الفصول غير متطابقة مع الكورس.');
        }

        $payloadSubIds = collect($sectionsPayload)
            ->flatMap(fn (array $section) => collect($section['subSections'] ?? [])->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        $courseSubIds = $subSectionsById->keys()->sort()->values();

        if ($payloadSubIds->toArray() !== $courseSubIds->toArray()) {
            abort(422, 'قائمة الأقسام الفرعية غير متطابقة مع الكورس.');
        }

        $payloadLessonIds = collect($sectionsPayload)
            ->flatMap(function (array $section) {
                $direct = collect($section['lessons'] ?? [])->pluck('id');
                $nested = collect($section['subSections'] ?? [])
                    ->flatMap(fn (array $sub) => collect($sub['lessons'] ?? [])->pluck('id'));

                return $direct->merge($nested);
            })
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        $courseLessonIds = $lessonsById->keys()->sort()->values();

        if ($payloadLessonIds->toArray() !== $courseLessonIds->toArray()) {
            abort(422, 'قائمة الدروس غير متطابقة مع الكورس.');
        }
    }

    /**
     * @param  Collection<int, Lesson>  $lessonsById
     */
    protected function lessonOrFail(Collection $lessonsById, int $lessonId): Lesson
    {
        $lesson = $lessonsById->get($lessonId);

        if (! $lesson) {
            abort(404);
        }

        return $lesson;
    }

    /**
     * @param  array<int, int>  $seenLessonIds
     */
    protected function assertLessonNotDuplicated(array &$seenLessonIds, int $lessonId): void
    {
        if (in_array($lessonId, $seenLessonIds, true)) {
            throw new HttpException(422, 'الدرس مكرر في الطلب.');
        }

        $seenLessonIds[] = $lessonId;
    }

    protected function placeDirectLesson(Lesson $lesson, Section $section, int $position): void
    {
        $lesson->update([
            'section_id' => $section->id,
            'sub_section_id' => null,
            'sort_order' => $position,
        ]);
    }

    protected function placeSubSectionLesson(Lesson $lesson, SubSection $subSection, int $position): void
    {
        $lesson->update([
            'section_id' => null,
            'sub_section_id' => $subSection->id,
            'sort_order' => $position,
        ]);
    }
}
