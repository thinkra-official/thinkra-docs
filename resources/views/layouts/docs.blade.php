<!DOCTYPE html>
<html lang="ar" dir="rtl" class="docs-theme-light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Thinkra Docs')</title>
    @if(!empty($seo) && $seo)
        <meta name="description" content="{{ $metaDescription ?? '' }}">
        <link rel="canonical" href="{{ $canonicalUrl ?? url()->current() }}">
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $ogTitle ?? '' }}">
        <meta property="og:description" content="{{ $metaDescription ?? '' }}">
        <meta property="og:url" content="{{ $canonicalUrl ?? url()->current() }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $ogTitle ?? '' }}">
        <meta name="twitter:description" content="{{ $metaDescription ?? '' }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/application-logo.css') }}">
    <link rel="stylesheet" href="{{ asset('css/docs-reader.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lesson-content.css') }}">
    @stack('head')
</head>
<body class="docs-shell">
    @if(!empty($preview) && $preview)
    <div class="docs-preview-banner" role="status">وضع المعاينة — المحتوى قد يتضمن مسودات غير منشورة</div>
    @endif

    <div class="docs-scroll-progress" aria-hidden="true">
        <div class="docs-scroll-progress-bar" id="docs-scroll-progress-bar"></div>
    </div>

    <header class="docs-topbar">
        <div class="docs-topbar-inner">
            <div class="docs-topbar-start">
                @isset($sections)
                <button type="button"
                        id="docs-burger-open"
                        class="docs-burger-btn"
                        aria-haspopup="dialog"
                        aria-controls="docs-course-menu"
                        aria-expanded="false"
                        aria-label="فتح محتوى الدورة">
                    <span class="docs-burger-btn-icon" aria-hidden="true">
                        <svg class="docs-burger-svg docs-burger-svg--menu" width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M4 7h16M4 12h16M4 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M17 17l3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <svg class="docs-burger-svg docs-burger-svg--close" width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="docs-burger-btn-copy">
                        <span class="docs-burger-btn-title">المحتوى</span>
                        <span class="docs-burger-btn-sub">الدورة</span>
                    </span>
                </button>
                @endisset
                <a href="{{ route('docs.search') }}" class="docs-brand" aria-label="THINKRA">
                    <x-application-logo />
                </a>
                @isset($course)
                <span class="docs-topbar-course hidden sm:inline">{{ $course->title }}</span>
                @endisset
            </div>
            <div class="docs-topbar-end">
                <a href="{{ route('docs.search') }}" class="docs-topbar-link hidden sm:inline">بحث</a>
                @auth
                <span class="docs-topbar-user hidden md:inline">{{ auth()->user()->name }}</span>
                @else
                <a href="{{ route('teacher.login') }}" class="docs-topbar-cta">دخول</a>
                @endauth
            </div>
        </div>
    </header>

    <div class="docs-viewer-layout @unless(isset($sections)) docs-viewer-layout--standalone @endunless">
        @isset($sections)
        <aside id="docs-sidebar-panel" class="docs-course-sidebar-panel docs-sidebar-desktop" aria-label="محتوى الدورة">
            @include('docs.partials.course-sidebar', [
                'course' => $course,
                'sections' => $sections,
                'currentLesson' => $currentLesson ?? null,
                'preview' => $preview ?? false,
                'progressStats' => $progressStats ?? null,
                'completedLessonIds' => $progressStats['completedIds'] ?? [],
                'expandedSections' => $expandedSections ?? [],
                'expandedSubSections' => $expandedSubSections ?? [],
            ])
        </aside>
        @endisset

        <main class="docs-main-area" id="docs-main">
            @yield('content')
        </main>
    </div>

    @isset($sections)
    @include('docs.partials.course-menu-modal', [
        'course' => $course,
        'sections' => $sections,
        'currentLesson' => $currentLesson ?? null,
        'preview' => $preview ?? false,
        'progressStats' => $progressStats ?? null,
        'completedLessonIds' => $progressStats['completedIds'] ?? [],
        'expandedSections' => $expandedSections ?? [],
        'expandedSubSections' => $expandedSubSections ?? [],
    ])
    @endisset

    @isset($course)
    @php
        $thinkraDocsViewerConfig = [
            'courseSlug' => $course->slug,
            'lessonId' => $currentLesson->id ?? null,
            'completedIds' => $progressStats['completedIds'] ?? [],
            'canTrackProgress' => $canTrackProgress ?? false,
            'completeUrl' => $completeUrl ?? null,
            'uncompleteUrl' => $uncompleteUrl ?? null,
            'storageKey' => 'thinkra_docs_progress_' . $course->id,
        ];
    @endphp
    <script>
        window.ThinkraDocsViewer = @json($thinkraDocsViewerConfig);
    </script>
    @endisset
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <script src="{{ asset('js/docs-reader.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
