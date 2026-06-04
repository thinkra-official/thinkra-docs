@extends('layouts.docs')

@section('title', 'بحث — Thinkra Docs')

@section('content')
@php
    $typeLabels = [
        'course' => 'دورة',
        'section' => 'فصل',
        'sub_section' => 'قسم',
        'lesson' => 'درس',
    ];
@endphp

<div class="docs-search-page">
    <div class="docs-search-inner">
        <header class="docs-search-hero">
            <div class="docs-search-hero-icon" aria-hidden="true">
                <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>
            <h1 class="docs-search-title">بحث في الوثائق</h1>
            <p class="docs-search-subtitle">ابحث في الدورات العامة والدروس المنشورة — فصول، أقسام، ومحتوى الدروس.</p>
        </header>

        <form method="GET" action="{{ route('docs.search') }}" class="docs-search-form" role="search">
            <label for="docs-search-input" class="sr-only">كلمة البحث</label>
            <div class="docs-search-field">
                <span class="docs-search-field-icon" aria-hidden="true">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                    </svg>
                </span>
                <input
                    type="search"
                    id="docs-search-input"
                    name="q"
                    value="{{ $query }}"
                    placeholder="ابحث عن درس، فصل، أو دورة..."
                    class="docs-search-input"
                    autocomplete="off"
                    autofocus
                >
                <button type="submit" class="docs-search-submit">بحث</button>
            </div>
        </form>

        @if($query === '')
        <p class="docs-search-hint">جرّب البحث باسم الدرس أو عنوان الدورة للوصول السريع للمحتوى.</p>
        @endif

        @if($query !== '' && $results->isEmpty())
        <div class="docs-search-empty" role="status">
            <p class="docs-search-empty-title">لا توجد نتائج</p>
            <p class="docs-search-empty-text">لم نجد شيئاً لـ «<strong>{{ $query }}</strong>». جرّب كلمات أخرى أو تحقق من الإملاء.</p>
        </div>
        @endif

        @if($results->isNotEmpty())
        <section class="docs-search-results" aria-label="نتائج البحث">
            <p class="docs-search-results-count">
                {{ $results->count() }} {{ $results->count() === 1 ? 'نتيجة' : 'نتائج' }}
                @if($query !== '')
                <span class="docs-search-results-query">لـ «{{ $query }}»</span>
                @endif
            </p>
            <ul class="docs-search-results-list">
                @foreach($results as $result)
                <li>
                    <a href="{{ $result['url'] }}" class="docs-search-result-card">
                        <span class="docs-search-result-body">
                            <span class="docs-search-result-badge docs-search-result-badge--{{ $result['type'] }}">
                                {{ $typeLabels[$result['type']] ?? $result['type'] }}
                            </span>
                            <span class="docs-search-result-title">{{ $result['title'] }}</span>
                            @if(!empty($result['courseTitle']))
                            <span class="docs-search-result-meta">{{ $result['courseTitle'] }}</span>
                            @endif
                            @if(!empty($result['excerpt']))
                            <span class="docs-search-result-excerpt">{{ $result['excerpt'] }}</span>
                            @endif
                        </span>
                        <span class="docs-search-result-arrow" aria-hidden="true">
                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </span>
                    </a>
                </li>
                @endforeach
            </ul>
        </section>
        @endif
    </div>
</div>
@endsection
