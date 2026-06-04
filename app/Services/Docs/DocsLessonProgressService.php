<?php

namespace App\Services\Docs;

use App\Models\Lesson;
use App\Models\LessonUserProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class DocsLessonProgressService
{
    /**
     * @param  Collection<int, Lesson>  $flatLessons
     * @return array{total: int, completed: int, percent: int, completedIds: int[]}
     */
    public function statsForUser(?User $user, Collection $flatLessons): array
    {
        $total = $flatLessons->count();
        $completedIds = $user
            ? $this->completedLessonIds($user, $flatLessons)
            : [];

        $completed = count(array_intersect(
            $completedIds,
            $flatLessons->pluck('id')->all()
        ));

        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'remaining' => max(0, $total - $completed),
            'percent' => $percent,
            'completedIds' => $completedIds,
        ];
    }

    /**
     * @param  Collection<int, Lesson>  $flatLessons
     * @return int[]
     */
    public function completedLessonIds(?User $user, Collection $flatLessons): array
    {
        if (! $user || $flatLessons->isEmpty()) {
            return [];
        }

        return LessonUserProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $flatLessons->pluck('id'))
            ->pluck('lesson_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function markComplete(User $user, Lesson $lesson): void
    {
        LessonUserProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            ['completed_at' => now()]
        );
    }

    public function isComplete(?User $user, Lesson $lesson, array $completedIds = []): bool
    {
        if ($user) {
            return in_array($lesson->id, $completedIds, true)
                || LessonUserProgress::query()
                    ->where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->exists();
        }

        return false;
    }

    public function estimateReadingMinutes(?string $html, ?string $objective = null): int
    {
        $text = strip_tags(($html ?? '').' '.($objective ?? ''));
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
        if ($text === '') {
            return 1;
        }

        $words = count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return max(1, (int) ceil($words / 180));
    }
}
