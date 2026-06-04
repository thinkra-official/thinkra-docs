@php
    $slideProgress = $progressStats ?? ['total' => 0, 'completed' => 0, 'percent' => 0];
@endphp

<div id="docs-slide-overlay" class="docs-slide-overlay" hidden></div>

<aside id="docs-slide-panel" class="docs-slide-panel" aria-label="محتوى الدورة" aria-hidden="true">
    <header class="docs-slide-panel__header">
        <div class="docs-slide-panel__header-text">
            <span class="docs-slide-panel__label">محتوى الدورة</span>
            <h2 class="docs-slide-panel__title">{{ $course->title }}</h2>
            @if(($slideProgress['total'] ?? 0) > 0)
            <p class="docs-slide-panel__meta">
                {{ $slideProgress['completed'] }}/{{ $slideProgress['total'] }} دروس · {{ $slideProgress['percent'] }}%
            </p>
            @endif
        </div>
        <button type="button" class="docs-slide-panel__close" id="docs-slide-close" aria-label="إغلاق">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
    </header>

    <div class="docs-slide-panel__body">
        @include('docs.partials.course-sidebar', [
            'course' => $course,
            'sections' => $sections,
            'currentLesson' => $currentLesson ?? null,
            'preview' => $preview ?? false,
            'progressStats' => $progressStats ?? null,
            'completedLessonIds' => $completedLessonIds ?? [],
            'expandedSections' => $expandedSections ?? [],
            'expandedSubSections' => $expandedSubSections ?? [],
            'hideHeader' => true,
        ])
    </div>
</aside>
