@extends('layouts.teacher')

@section('title', 'كورساتي')
@section('page-title', 'لوحة التحكم')
@section('page-subtitle')
مرحباً، {{ auth()->user()->name }}
@endsection

@section('content')
@php
    use App\Models\Lesson;
    $coursesCount = $courses->count();
    $lessonsCount = $courses->sum(
        fn ($course) => $course->sections->sum(
            fn ($section) => $section->directLessons->count()
                + $section->subSections->sum(fn ($sub) => $sub->lessons->count())
        )
    );
    $courseIds = $courses->pluck('id');
    $recentLessons = Lesson::query()
        ->where(function ($q) use ($courseIds) {
            $q->whereHas('section', fn ($sq) => $sq->whereIn('course_id', $courseIds))
                ->orWhereHas('subSection.section', fn ($sq) => $sq->whereIn('course_id', $courseIds));
        })
        ->with(['section.course', 'subSection.section.course'])
        ->latest('updated_at')
        ->limit(5)
        ->get();
@endphp

@include('components.alert')

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-6 mb-8">
    <x-dashboard.stat-card label="كورساتي" :value="$coursesCount" icon-bg="bg-violet-100" icon-color="text-thinkra-purple">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        </x-slot:icon>
    </x-dashboard.stat-card>

    <x-dashboard.stat-card label="إجمالي الدروس" :value="$lessonsCount" icon-bg="bg-sky-100" icon-color="text-sky-600">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </x-slot:icon>
    </x-dashboard.stat-card>

    <x-dashboard.stat-card label="آخر التحديثات" :value="$recentLessons->count()" icon-bg="bg-amber-100" icon-color="text-amber-600">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </x-slot:icon>
    </x-dashboard.stat-card>

    <x-dashboard.stat-card label="الحالة" value="نشط" icon-bg="bg-emerald-100" icon-color="text-emerald-600">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </x-slot:icon>
    </x-dashboard.stat-card>
</div>

@if($recentLessons->isNotEmpty())
<div class="thinkra-card p-6 mb-8">
    <h2 class="text-lg font-bold text-thinkra-navy mb-4">آخر التحديثات</h2>
    <ul class="space-y-3">
        @foreach($recentLessons as $lesson)
        @php
            $section = $lesson->resolveSection();
            $course = $section->course;
            $editParams = $lesson->isInSubSection()
                ? \App\Support\NestedCourseRoute::subSectionLesson($course, $section, $lesson->subSection, $lesson)
                : \App\Support\NestedCourseRoute::sectionLesson($course, $section, $lesson);
            $editRoute = $lesson->isInSubSection() ? 'teacher.lessons.edit' : 'teacher.section-lessons.edit';
        @endphp
        @if($course && $section)
        <li class="flex items-center justify-between gap-4 p-3 rounded-xl bg-[#f6f7fb]">
            <div class="min-w-0">
                <p class="font-semibold text-thinkra-navy truncate">{{ $lesson->title }}</p>
                <p class="text-xs text-slate-500 truncate">{{ $course->title }}</p>
            </div>
            <a href="{{ route($editRoute, $editParams) }}"
               class="text-xs text-thinkra-purple font-semibold shrink-0 hover:underline">تعديل</a>
        </li>
        @endif
        @endforeach
    </ul>
</div>
@endif

<div id="courses">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-thinkra-navy">كورساتي</h2>
        <a href="{{ route('teacher.courses.index') }}" class="text-sm text-thinkra-purple font-semibold hover:underline">عرض الكل</a>
    </div>

    @include('teacher.courses._cards', ['courses' => $courses])
</div>
@endsection
