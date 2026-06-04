@extends('layouts.docs')

@section('title', $lesson->title.' — '.$course->title)

@section('content')
<div class="lesson-view">
    <div class="lesson-view-grid">
        <div class="lesson-view-main">
            <header class="lesson-hero" id="lesson-hero">
                <nav class="lesson-breadcrumb" aria-label="مسار الدرس">
                    <a href="{{ ($preview ?? false) ? route('docs.preview.course', $course->slug) : route('docs.course', $course->slug) }}">{{ $course->title }}</a>
                    <span class="lesson-breadcrumb-sep" aria-hidden="true">/</span>
                    <span>{{ $lessonSection->title }}</span>
                    @if($lessonSubSection)
                    <span class="lesson-breadcrumb-sep" aria-hidden="true">/</span>
                    <span>{{ $lessonSubSection->title }}</span>
                    @endif
                </nav>

                <h1 class="lesson-hero-title">{{ $lesson->title }}</h1>

                <div class="lesson-hero-meta">
                    <span class="lesson-meta-pill">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        ~{{ $readingMinutes }} دقيقة قراءة
                    </span>
                    @if($progressStats['total'] > 0)
                    <span class="lesson-meta-pill lesson-meta-progress">
                        {{ $progressStats['completed'] }} من {{ $progressStats['total'] }} دروس
                        · {{ $progressStats['percent'] }}%
                    </span>
                    @endif
                </div>

                @if($progressStats['total'] > 0)
                <div class="lesson-course-progress">
                    <div class="lesson-course-progress-label">
                        <span>تقدمك في الدورة</span>
                        <span class="font-semibold">{{ $progressStats['percent'] }}%</span>
                    </div>
                    <div class="lesson-course-progress-track" role="progressbar"
                         aria-valuenow="{{ $progressStats['percent'] }}" aria-valuemin="0" aria-valuemax="100"
                         id="lesson-course-progress-bar">
                        <div class="lesson-course-progress-fill" style="width: {{ $progressStats['percent'] }}%"></div>
                    </div>
                    <p class="lesson-course-progress-hint">
                        {{ $progressStats['remaining'] }} دروس متبقية
                    </p>
                </div>
                @endif
            </header>

            @if(count($toc) > 0)
            <nav class="lesson-toc-mobile docs-toc-panel" aria-label="فهرس المحتوى">
                <p class="lesson-toc-heading">في هذه الصفحة</p>
                <ul class="lesson-toc-list">
                    @foreach($toc as $item)
                    <li class="lesson-toc-item level-{{ $item['level'] }}">
                        <a href="#{{ $item['id'] }}" data-toc-link>{{ $item['text'] }}</a>
                    </li>
                    @endforeach
                </ul>
            </nav>
            @endif

            @if(filled($lesson->objective))
            <div class="lesson-callout lesson-callout-objective">
                <p class="lesson-callout-label">هدف الدرس</p>
                <div class="lesson-callout-body whitespace-pre-wrap">{{ $lesson->objective }}</div>
            </div>
            @endif

            <article class="lesson-article docs-prose thinkra-lesson-prose" id="lesson-content">
                {!! $contentHtml !!}
            </article>

            @if(!empty($showTeacherNotes) && $showTeacherNotes)
            <div class="lesson-callout lesson-callout-notes">
                <p class="lesson-callout-label">ملاحظات المعلم</p>
                <div class="lesson-callout-body text-sm whitespace-pre-wrap">{!! nl2br(e($lesson->teacher_notes)) !!}</div>
            </div>
            @endif

            <div class="lesson-actions">
                @if($canTrackProgress)
                <button type="button"
                        id="lesson-complete-btn"
                        class="lesson-complete-btn {{ $lessonComplete ? 'is-done' : '' }}"
                        data-complete-url="{{ $completeUrl }}"
                        data-uncomplete-url="{{ $uncompleteUrl }}"
                        data-lesson-id="{{ $lesson->id }}"
                        data-auto-advance="1"
                        aria-pressed="{{ $lessonComplete ? 'true' : 'false' }}">
                    <span class="lesson-complete-icon" aria-hidden="true">✓</span>
                    <span>{{ $lessonComplete ? 'إلغاء إكمال الدرس' : 'تحديد الدرس كمكتمل' }}</span>
                </button>
                @else
                <button type="button"
                        id="lesson-complete-btn"
                        class="lesson-complete-btn"
                        data-lesson-id="{{ $lesson->id }}"
                        data-guest-progress="1">
                    <span class="lesson-complete-icon" aria-hidden="true">✓</span>
                    <span>تحديد الدرس كمكتمل</span>
                </button>
                <p class="lesson-guest-hint text-xs">يُحفظ التقدم محلياً على هذا الجهاز. سجّل الدخول للمزامنة.</p>
                @endif
            </div>

            <footer class="lesson-nav-cards">
                @if($previousLesson)
                @php
                    $prevUrl = ($preview ?? false)
                        ? route('docs.preview.lesson', [$course->slug, $previousLesson->slug])
                        : route('docs.lesson', [$course->slug, $previousLesson->slug]);
                @endphp
                <a href="{{ $prevUrl }}" class="lesson-nav-card lesson-nav-prev" data-nav="prev" rel="prev">
                    <span class="lesson-nav-label">الدرس السابق</span>
                    <span class="lesson-nav-title">← {{ $previousLesson->title }}</span>
                </a>
                @else
                <div class="lesson-nav-spacer" aria-hidden="true"></div>
                @endif

                @if($nextLesson)
                @php
                    $nextUrl = ($preview ?? false)
                        ? route('docs.preview.lesson', [$course->slug, $nextLesson->slug])
                        : route('docs.lesson', [$course->slug, $nextLesson->slug]);
                @endphp
                <a href="{{ $nextUrl }}" class="lesson-nav-card lesson-nav-next" data-nav="next" rel="next">
                    <span class="lesson-nav-label">الدرس التالي</span>
                    <span class="lesson-nav-title">{{ $nextLesson->title }} →</span>
                </a>
                @endif
            </footer>
        </div>

        @if(count($toc) > 0)
        <aside class="lesson-toc-desktop docs-toc-panel hidden lg:block" aria-label="فهرس المحتوى">
            <p class="lesson-toc-heading">في هذه الصفحة</p>
            <ul class="lesson-toc-list" id="lesson-toc-sticky">
                @foreach($toc as $item)
                <li class="lesson-toc-item level-{{ $item['level'] }}">
                    <a href="#{{ $item['id'] }}" data-toc-link>{{ $item['text'] }}</a>
                </li>
                @endforeach
            </ul>
        </aside>
        @endif
    </div>
</div>
@endsection
