<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('css/docs-reader.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lesson-content.css') }}">
    @stack('head')
</head>
<body class="docs-shell font-sans antialiased" style="font-family: Cairo, system-ui, sans-serif;">
    @if(!empty($preview) && $preview)
    <div class="docs-preview-banner">وضع المعاينة — المحتوى قد يتضمن مسودات غير منشورة</div>
    @endif

    <div class="docs-progress" aria-hidden="true">
        <div class="docs-progress-bar" id="docs-progress-bar"></div>
    </div>

    <header class="docs-header">
        <div class="flex items-center justify-between gap-4 px-4 py-3 max-w-[1400px] mx-auto">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" id="docs-menu-toggle" class="lg:hidden shrink-0 p-2 rounded-lg border border-slate-200" aria-label="القائمة">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ route('docs.search') }}" class="font-bold text-lg shrink-0" style="color:#9e27b5">Thinkra Docs</a>
                @isset($course)
                <span class="text-sm truncate opacity-60 hidden sm:inline">/ {{ $course->title }}</span>
                @endisset
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('docs.search') }}" class="text-sm px-3 py-1.5 rounded-lg border hidden sm:inline" style="border-color:var(--docs-border)">بحث</a>
                @auth
                <span class="text-xs opacity-60 hidden md:inline">{{ auth()->user()->name }}</span>
                @else
                <a href="{{ route('teacher.login') }}" class="text-sm px-3 py-1.5 rounded-lg text-white" style="background:#9e27b5">دخول</a>
                @endauth
            </div>
        </div>
    </header>

    <div class="docs-layout">
        @isset($sections)
        <aside id="docs-sidebar-panel" class="docs-sidebar-panel course-sidebar">
            @include('docs.partials.sidebar', [
                'course' => $course,
                'sections' => $sections,
                'currentLesson' => $currentLesson ?? null,
                'preview' => $preview ?? false,
            ])
        </aside>
        @endisset

        <main class="docs-main min-w-0">
            @yield('content')
        </main>
    </div>

    <script src="{{ asset('js/docs-reader.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
