@extends('layouts.teacher')

@section('title', 'تعديل الدرس')

@php
    use App\Support\NestedCourseRoute;
    $inSubSection = $inSubSection ?? ($subSection !== null);
    if ($inSubSection) {
        $lessonRoute = NestedCourseRoute::subSectionLesson($course, $section, $subSection, $lesson);
        $lessonDestroyRoute = NestedCourseRoute::subSectionLessonDestroy($course, $section, $subSection, $lesson);
        $versionPlaceholder = NestedCourseRoute::subSectionVersion($course, $section, $subSection, $lesson, '__VERSION__');
        $statusRoute = 'teacher.lessons.status';
        $updateRoute = 'teacher.lessons.update';
        $destroyRoute = 'teacher.lessons.destroy';
        $autosaveRoute = 'teacher.lessons.autosave';
        $versionShowRoute = 'teacher.lessons.versions.show';
        $versionRestoreRoute = 'teacher.lessons.versions.restore';
    } else {
        $lessonRoute = NestedCourseRoute::sectionLesson($course, $section, $lesson);
        $lessonDestroyRoute = NestedCourseRoute::sectionLessonDestroy($course, $section, $lesson);
        $versionPlaceholder = NestedCourseRoute::sectionVersion($course, $section, $lesson, '__VERSION__');
        $statusRoute = 'teacher.section-lessons.status';
        $updateRoute = 'teacher.section-lessons.update';
        $destroyRoute = 'teacher.section-lessons.destroy';
        $autosaveRoute = 'teacher.section-lessons.autosave';
        $versionShowRoute = 'teacher.section-lessons.versions.show';
        $versionRestoreRoute = 'teacher.section-lessons.versions.restore';
    }
    $versionShowUrlTemplate = route($versionShowRoute, $versionPlaceholder);
    $versionRestoreUrlTemplate = route($versionRestoreRoute, $versionPlaceholder);
@endphp

@section('content')
<a href="{{ route('teacher.courses.show', $course) }}" class="text-sm text-thinkra-purple hover:underline">← {{ $course->title }}</a>
<div class="flex flex-wrap items-center justify-between gap-2 mt-1">
    <p class="text-xs text-slate-500">
        فصل: {{ $section->title }}
        @if($inSubSection && $subSection)
        / قسم: {{ $subSection->title }}
        @endif
    </p>
    @if($course->slug && $lesson->slug)
    <a href="{{ route('docs.preview.lesson', [$course->slug, $lesson->slug]) }}" target="_blank" rel="noopener"
       class="text-xs font-semibold text-thinkra-purple hover:underline">معاينة القارئ ↗</a>
    @endif
</div>

@include('components.alert')

@if($canEdit)
{{-- شريط الحالة والحفظ --}}
<div class="thinkra-card p-4 mt-4 mb-4" x-data="{ tab: 'edit' }">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
        <div class="flex flex-wrap gap-2">
            @foreach([\App\Enums\LessonStatus::Draft, \App\Enums\LessonStatus::Ready, \App\Enums\LessonStatus::Published] as $statusOption)
            <form method="POST" action="{{ route($statusRoute, $lessonRoute) }}" class="inline">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="{{ $statusOption->value }}">
                <button type="submit"
                        class="px-4 py-2 rounded-full text-sm font-semibold transition
                        {{ $lesson->status === $statusOption
                            ? 'bg-thinkra-purple text-white shadow'
                            : 'bg-slate-100 text-slate-600 hover:bg-violet-50' }}">
                    <span class="inline-block w-2 h-2 rounded-full mr-1 {{ $lesson->status === $statusOption ? 'bg-white' : 'bg-thinkra-purple' }}"></span>
                    {{ match($statusOption) {
                        \App\Enums\LessonStatus::Draft => 'Draft',
                        \App\Enums\LessonStatus::Ready => 'Ready',
                        \App\Enums\LessonStatus::Published => 'Published',
                        default => $statusOption->label(),
                    } }}
                </button>
            </form>
            @endforeach
        </div>
        <div class="text-left text-sm">
            <p id="save-status" data-state="saved" class="font-medium text-emerald-600">تم الحفظ</p>
            <p class="text-slate-500 text-xs mt-0.5">
                آخر حفظ: <span id="last-save-time">{{ $lesson->updated_at?->timezone(config('app.timezone'))->format('H:i:s') }}</span>
            </p>
        </div>
    </div>

    <div id="draft-recovery-banner" class="hidden mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
        <p class="font-semibold mb-2">يوجد مسودة محلية أحدث من آخر حفظ على الخادم</p>
        <p class="text-xs mb-3">يمكن استعادتها بعد انقطاع الاتصال أو إغلاق المتصفح.</p>
        <div class="flex gap-2">
            <button type="button" id="draft-restore-btn" class="thinkra-btn-primary px-3 py-1.5 text-xs">استعادة المسودة</button>
            <button type="button" id="draft-discard-btn" class="rounded-lg border px-3 py-1.5 text-xs">تجاهل</button>
        </div>
    </div>

    <div class="flex gap-2 border-b border-slate-100 pb-2 mb-4">
        <button type="button" @click="tab = 'edit'"
                :class="tab === 'edit' ? 'text-thinkra-purple border-b-2 border-thinkra-purple font-semibold' : 'text-slate-500'"
                class="px-4 py-2 text-sm">تحرير</button>
        <button type="button" @click="tab = 'versions'"
                :class="tab === 'versions' ? 'text-thinkra-purple border-b-2 border-thinkra-purple font-semibold' : 'text-slate-500'"
                class="px-4 py-2 text-sm">Versions</button>
        <button type="button" @click="tab = 'changelog'"
                :class="tab === 'changelog' ? 'text-thinkra-purple border-b-2 border-thinkra-purple font-semibold' : 'text-slate-500'"
                class="px-4 py-2 text-sm">سجل التعديلات</button>
    </div>

    <div x-show="tab === 'edit'" x-cloak>
        <form method="POST" action="{{ route($updateRoute, $lessonRoute) }}" class="space-y-5" id="lesson-form">
            @csrf @method('PUT')

            <div class="thinkra-card p-5">
                <label class="block text-sm font-semibold mb-1">عنوان الدرس</label>
                <input name="title" value="{{ old('title', $lesson->title) }}" required class="w-full rounded-xl border px-4 py-2.5">
            </div>

            <div class="thinkra-card p-5">
                <label class="block text-sm font-semibold mb-1 text-thinkra-navy">الهدف</label>
                <textarea name="objective" rows="3" class="w-full rounded-xl border px-4 py-2.5 text-sm">{{ old('objective', $lesson->objective) }}</textarea>
            </div>

            <div class="thinkra-card p-5">
                <label class="block text-sm font-semibold mb-2 text-thinkra-navy">المحتوى الرئيسي</label>
                <p class="text-xs text-slate-400 mb-3">يُحفظ تلقائياً كل 10 ثوانٍ أو بعد 3 ثوانٍ من التوقف عن الكتابة.</p>
                <x-ckeditor name="main_content" :value="old('main_content', $lesson->main_content)" editor-id="main_content" />
            </div>

            <div class="thinkra-card p-5 border-amber-200/80 bg-amber-50/30">
                <label class="block text-sm font-semibold mb-1 text-amber-900">ملاحظات الأستاذ الخاصة</label>
                <p class="text-xs text-amber-700/80 mb-2">خاصة بك وللإدارة فقط.</p>
                <textarea name="teacher_notes" rows="4" class="w-full rounded-xl border border-amber-200 px-4 py-2.5 text-sm bg-white">{{ old('teacher_notes', $lesson->teacher_notes) }}</textarea>
            </div>

            <button type="submit" class="thinkra-btn-primary px-8 py-3">حفظ الآن</button>
        </form>
    </div>

    <div x-show="tab === 'versions'" x-cloak>
        <div id="version-preview-panel" class="hidden thinkra-card p-5 mb-4 border-thinkra-purple/30"></div>

        @if($versions->isEmpty())
        <p class="text-slate-400 text-sm text-center py-8">لا توجد نسخ محفوظة بعد. تُنشأ نسخة تلقائياً عند كل حفظ مهم.</p>
        @else
        <ul class="space-y-3">
            @foreach($versions as $version)
            <li class="thinkra-card p-4 flex flex-wrap justify-between items-center gap-3">
                <div>
                    <p class="font-semibold text-thinkra-navy">{{ $version->created_at->format('Y-m-d H:i') }}</p>
                    <p class="text-xs text-slate-500">{{ $version->author?->name ?? '—' }} — {{ \Illuminate\Support\Str::limit($version->title, 40) }}</p>
                </div>
                <div class="flex gap-2 text-xs">
                    <button type="button" onclick="previewLessonVersion({{ $version->id }})"
                            class="rounded-lg border px-3 py-1.5 hover:bg-slate-50">Preview</button>
                    <button type="button" onclick="restoreLessonVersion({{ $version->id }})"
                            class="rounded-lg bg-thinkra-purple text-white px-3 py-1.5">Restore</button>
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </div>

    <div x-show="tab === 'changelog'" x-cloak>
        @php
            $fieldLabels = [
                'title' => 'العنوان',
                'objective' => 'الهدف',
                'main_content' => 'المحتوى الرئيسي',
                'teacher_notes' => 'ملاحظات الأستاذ',
            ];
        @endphp
        @if($changeLogs->isEmpty())
        <p class="text-slate-400 text-sm text-center py-8">لا توجد تعديلات مسجّلة بعد.</p>
        @else
        <ul class="space-y-3">
            @foreach($changeLogs as $log)
            <li class="thinkra-card p-4">
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-semibold text-thinkra-navy">{{ $log->user?->name }}</span>
                    <span class="text-slate-400 text-xs">{{ $log->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <p class="text-xs text-slate-600">
                    تغيّر:
                    {{ collect($log->changes ?? [])->keys()->map(fn ($k) => $fieldLabels[$k] ?? $k)->implode('، ') }}
                </p>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
</div>

<div class="thinkra-card p-5 border-red-200 mt-6">
    <form method="POST" action="{{ route($destroyRoute, $lessonDestroyRoute) }}"
          onsubmit="return confirm('هل تريد حذف هذا الدرس نهائياً؟')">
        @csrf @method('DELETE')
        <button type="submit" class="text-sm text-red-600 font-semibold hover:underline">حذف الدرس</button>
    </form>
</div>

@push('scripts')
<script>
window.ThinkraLessonEditor = {
    canEdit: true,
    lessonId: {{ $lesson->id }},
    autosaveUrl: @json(route($autosaveRoute, $lessonRoute)),
    versionShowUrlTemplate: @json($versionShowUrlTemplate),
    versionRestoreUrlTemplate: @json($versionRestoreUrlTemplate),
    serverSavedAt: @json($lesson->updated_at?->toIso8601String()),
    lastSaveFormatted: @json($lesson->updated_at?->timezone(config('app.timezone'))->format('H:i:s')),
    idleMs: 3000,
    intervalMs: 10000,
};
</script>
<script src="{{ asset('js/lesson-editor.js') }}"></script>
@endpush

@else
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-800 text-sm mt-6">
    لديك صلاحية مشاهدة فقط (Viewer) ولا يمكنك تعديل هذا الدرس.
</div>
@endif
@endsection
