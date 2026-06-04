@extends('layouts.admin')

@section('title', $lesson->title)

@push('head')
<link rel="stylesheet" href="{{ asset_version('css/lesson-content.css') }}">
@endpush

@php
    $lessonShowUrl = $subSection
        ? route('admin.courses.lessons.show', \App\Support\NestedCourseRoute::subSectionLesson($course, $section, $subSection, $lesson))
        : route('admin.courses.section-lessons.show', \App\Support\NestedCourseRoute::sectionLesson($course, $section, $lesson));
@endphp
<!-- lesson show url: {{ $lessonShowUrl }} -->

@section('content')
<a href="{{ route('admin.courses.show', $course) }}" class="text-sm text-thinkra-purple hover:underline">← {{ $course->title }}</a>
<h1 class="text-2xl font-bold text-thinkra-navy mt-2 mb-1">{{ $lesson->title }}</h1>
<p class="text-sm text-slate-500 mb-2">
    فصل: {{ $section->title }}
    @if($subSection)
    / قسم: {{ $subSection->title }}
    @endif
</p>
<span class="inline-block px-2 py-1 rounded-full text-xs {{ $lesson->status->badgeClass() }}">{{ $lesson->status->label() }}</span>

<div class="mt-6 space-y-4">
    @if($lesson->objective)
    <div class="bg-white rounded-2xl border p-5">
        <h2 class="font-semibold text-thinkra-navy mb-2">الهدف</h2>
        <div class="text-slate-700 whitespace-pre-wrap">{{ $lesson->objective }}</div>
    </div>
    @endif

    <div class="bg-white rounded-2xl border p-5">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 class="font-semibold text-thinkra-navy">المحتوى الرئيسي</h2>
            @php
                $editRoute = $subSection
                    ? 'admin.courses.lessons.edit'
                    : 'admin.courses.section-lessons.edit';
                $editParams = $subSection
                    ? \App\Support\NestedCourseRoute::subSectionLesson($course, $section, $subSection, $lesson)
                    : \App\Support\NestedCourseRoute::sectionLesson($course, $section, $lesson);
            @endphp
            <a href="{{ route($editRoute, $editParams) }}" class="thinkra-btn-primary px-4 py-2 text-sm">تعديل محتوى الدرس</a>
        </div>
        @if(filled($lesson->main_content))
        <div class="thinkra-lesson-prose lesson-content">{!! $lesson->main_content !!}</div>
        @else
        <p class="text-slate-400 text-sm">لا يوجد محتوى بعد. اضغط «تعديل محتوى الدرس» لإضافة النص والتنسيق.</p>
        @endif
    </div>

    @if($lesson->teacher_notes)
    <div class="bg-amber-50 rounded-2xl border border-amber-200 p-5">
        <h2 class="font-semibold text-amber-900 mb-2">ملاحظات الأستاذ (خاصة)</h2>
        <div class="text-amber-900/90 whitespace-pre-wrap text-sm">{{ $lesson->teacher_notes }}</div>
    </div>
    @endif
</div>

@php
    $lessonDestroyUrl = $subSection
        ? route('admin.courses.lessons.destroy', \App\Support\NestedCourseRoute::subSectionLessonDestroy($course, $section, $subSection, $lesson))
        : route('admin.courses.section-lessons.destroy', \App\Support\NestedCourseRoute::sectionLessonDestroy($course, $section, $lesson));
@endphp
<div class="mt-8 thinkra-card p-5 border-red-200">
    <!-- lesson delete action: {{ $lessonDestroyUrl }} -->
    <form method="POST" action="{{ $lessonDestroyUrl }}"
          onsubmit="return confirm('هل تريد حذف هذا الدرس نهائياً؟')">
        @csrf
        @method('DELETE')
        <input type="hidden" name="_method" value="DELETE">
        <button type="submit" class="text-sm text-red-600 font-semibold hover:underline">حذف الدرس</button>
    </form>
</div>
@endsection
