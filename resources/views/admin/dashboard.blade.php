@extends('layouts.admin')

@section('title', 'لوحة الإدارة')
@section('page-title', 'لوحة التحكم')
@section('page-subtitle', 'مرحباً بك في Thinkra Docs')

@section('content')
@php
    use App\Models\Lesson;
    $lessonsCount = Lesson::count();
    $recentLessons = Lesson::query()
        ->with(['section.course', 'subSection.section.course'])
        ->latest('updated_at')
        ->limit(5)
        ->get();
@endphp

@include('components.alert')

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-6 mb-8">
    <x-dashboard.stat-card label="إجمالي الكورسات" :value="$coursesCount" icon-bg="bg-violet-100" icon-color="text-thinkra-purple">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        </x-slot:icon>
    </x-dashboard.stat-card>

    <x-dashboard.stat-card label="إجمالي الأساتذة" :value="$teachersCount" icon-bg="bg-emerald-100" icon-color="text-emerald-600">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
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
</div>

<p class="text-xs text-slate-500 -mt-4 mb-6">أساتذة نشطون: <span class="font-semibold text-thinkra-navy">{{ $activeTeachers }}</span> من {{ $teachersCount }}</p>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 thinkra-card p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-bold text-thinkra-navy">آخر التحديثات</h2>
            <span class="text-xs text-slate-400">أحدث الدروس المعدّلة</span>
        </div>
        @if($recentLessons->isEmpty())
        <p class="text-slate-400 text-sm text-center py-8">لا توجد تحديثات بعد</p>
        @else
        <ul class="space-y-3">
            @foreach($recentLessons as $lesson)
            @php
                $section = $lesson->resolveSection();
                $course = $section->course;
                $sub = $lesson->subSection;
            @endphp
            <li class="flex items-center justify-between gap-4 p-3 rounded-xl bg-[#f6f7fb] hover:bg-violet-50/50 transition">
                <div class="min-w-0">
                    <p class="font-semibold text-thinkra-navy truncate">{{ $lesson->title }}</p>
                    <p class="text-xs text-slate-500 mt-0.5 truncate">
                        {{ $course?->title ?? '—' }}
                        @if($section) · {{ $section->title }} @endif
                    </p>
                </div>
                <div class="text-left shrink-0">
                    <span class="text-xs text-slate-400 block">{{ $lesson->updated_at->diffForHumans() }}</span>
                    @if($course && $section)
                    <a href="{{ route($lesson->isInSubSection() ? 'admin.courses.lessons.edit' : 'admin.courses.section-lessons.edit', $lesson->isInSubSection() ? \App\Support\NestedCourseRoute::subSectionLesson($course, $section, $sub, $lesson) : \App\Support\NestedCourseRoute::sectionLesson($course, $section, $lesson)) }}"
                       class="text-xs text-thinkra-purple font-semibold hover:underline">عرض</a>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </div>

    <div class="thinkra-card p-6">
        <h2 class="text-lg font-bold text-thinkra-navy mb-5">إجراءات سريعة</h2>
        <div class="space-y-3">
            <a href="{{ route('admin.teachers.create') }}" class="thinkra-btn-primary block text-center px-5 py-3 text-sm">
                إضافة أستاذ
            </a>
            <a href="{{ route('admin.courses.create') }}" class="thinkra-btn-secondary block text-center px-5 py-3 text-sm">
                إضافة كورس
            </a>
            <a href="{{ route('admin.courses.index') }}" class="block text-center px-5 py-3 text-sm rounded-xl border border-slate-200 text-thinkra-navy font-semibold hover:bg-slate-50">
                عرض كل الكورسات
            </a>
        </div>
    </div>
</div>
@endsection
