<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonChangeLog;
use App\Models\LessonVersion;
use App\Models\User;
class LessonContentService
{
    protected const TRACKED = ['title', 'objective', 'main_content', 'teacher_notes'];

    /**
     * @return array{saved_at: string, version_id: int|null, changes: array}
     */
    public function save(Lesson $lesson, User $user, array $data, bool $forceVersion = false): array
    {
        $before = $lesson->only(self::TRACKED);
        $payload = $this->normalizePayload($data);

        $changes = $this->diff($before, $payload);

        if (empty($changes)) {
            return [
                'saved_at' => $lesson->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                'version_id' => null,
                'changes' => [],
            ];
        }

        $lesson->update($payload);
        $lesson->refresh();

        $this->recordChangeLog($lesson, $user, $changes);

        $versionId = null;
        if ($forceVersion || $this->shouldCreateVersion($lesson, $payload)) {
            $version = $this->createVersion($lesson, $user, $payload);
            $versionId = $version->id;
        }

        return [
            'saved_at' => $lesson->updated_at->toIso8601String(),
            'version_id' => $versionId,
            'changes' => $changes,
        ];
    }

    public function createVersion(Lesson $lesson, User $user, ?array $payload = null): LessonVersion
    {
        $payload = $payload ?? $lesson->only(self::TRACKED);

        return LessonVersion::create([
            'lesson_id' => $lesson->id,
            'title' => $payload['title'] ?? $lesson->title,
            'objective' => $payload['objective'] ?? null,
            'main_content' => $payload['main_content'] ?? null,
            'teacher_notes' => $payload['teacher_notes'] ?? null,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);
    }

    public function restoreVersion(Lesson $lesson, LessonVersion $version, User $user): array
    {
        $payload = $version->only(self::TRACKED);

        return $this->save($lesson, $user, $payload, forceVersion: true);
    }

    /**
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function diff(array $before, array $after): array
    {
        $changes = [];

        foreach (self::TRACKED as $field) {
            $old = $this->normalizeValue($before[$field] ?? null);
            $new = $this->normalizeValue($after[$field] ?? null);

            if ($old !== $new) {
                $changes[$field] = [
                    'old' => $before[$field] ?? null,
                    'new' => $after[$field] ?? null,
                ];
            }
        }

        return $changes;
    }

    public function changeSummary(array $changes): string
    {
        $labels = [
            'title' => 'العنوان',
            'objective' => 'الهدف',
            'main_content' => 'المحتوى الرئيسي',
            'teacher_notes' => 'ملاحظات الأستاذ',
        ];

        return collect($changes)
            ->keys()
            ->map(fn ($key) => $labels[$key] ?? $key)
            ->implode('، ');
    }

    protected function recordChangeLog(Lesson $lesson, User $user, array $changes): void
    {
        LessonChangeLog::create([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }

    protected function shouldCreateVersion(Lesson $lesson, array $payload): bool
    {
        $hash = $this->contentHash($payload);

        $lastVersion = $lesson->versions()->first();

        if (! $lastVersion) {
            return true;
        }

        return $this->contentHash($lastVersion->only(self::TRACKED)) !== $hash;
    }

    protected function contentHash(array $payload): string
    {
        return hash('sha256', json_encode($this->normalizePayload($payload)));
    }

    protected function normalizePayload(array $data): array
    {
        return [
            'title' => trim((string) ($data['title'] ?? '')),
            'objective' => $this->nullableString($data['objective'] ?? null),
            'main_content' => $this->nullableString($data['main_content'] ?? null),
            'teacher_notes' => $this->nullableString($data['teacher_notes'] ?? null),
        ];
    }

    protected function normalizeValue(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim($value);
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
