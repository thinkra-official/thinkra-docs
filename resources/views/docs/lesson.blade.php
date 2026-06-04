@extends('layouts.docs')

@section('title', $lesson->title.' — '.$course->title)

@section('content')
<div class="docs-with-toc">
    <article class="docs-article min-w-0">
        @if(count($toc) > 0)
        <nav class="docs-toc-mobile docs-toc rounded-xl border p-3 mb-6" style="border-color:var(--docs-border);background:var(--docs-surface)" aria-label="فهرس المحتوى">
            <p class="font-semibold text-sm mb-2" style="color:var(--docs-accent-dark)">في هذه الصفحة</p>
            @foreach($toc as $item)
            <a href="#{{ $item['id'] }}" class="level-{{ $item['level'] }}">{{ $item['text'] }}</a>
            @endforeach
        </nav>
        @endif

        <h1>{{ $lesson->title }}</h1>

        @if(filled($lesson->objective))
        <div class="docs-objective">
            <p class="text-sm font-semibold mb-1" style="color:var(--docs-accent)">الهدف</p>
            <div class="whitespace-pre-wrap">{{ $lesson->objective }}</div>
        </div>
        @endif

        <div class="docs-prose">
            {!! $contentHtml !!}
        </div>

        @if(!empty($showTeacherNotes) && $showTeacherNotes)
        <div class="docs-notes">
            <p class="text-sm font-semibold mb-2" style="color:var(--docs-accent)">ملاحظات المعلم</p>
            <div class="whitespace-pre-wrap text-sm">{!! nl2br(e($lesson->teacher_notes)) !!}</div>
        </div>
        @endif

        <footer class="docs-nav-footer">
            @if($previousLesson)
            @php $prevUrl = ($preview ?? false) ? route('docs.preview.lesson', [$course->slug, $previousLesson->slug]) : route('docs.lesson', [$course->slug, $previousLesson->slug]); @endphp
            <a href="{{ $prevUrl }}" class="docs-nav-card text-right">
                <span class="text-xs opacity-60 block mb-1">الدرس السابق</span>
                <span class="font-semibold">← {{ $previousLesson->title }}</span>
            </a>
            @else
            <div></div>
            @endif

            @if($nextLesson)
            @php $nextUrl = ($preview ?? false) ? route('docs.preview.lesson', [$course->slug, $nextLesson->slug]) : route('docs.lesson', [$course->slug, $nextLesson->slug]); @endphp
            <a href="{{ $nextUrl }}" class="docs-nav-card text-left">
                <span class="text-xs opacity-60 block mb-1">الدرس التالي</span>
                <span class="font-semibold">{{ $nextLesson->title }} →</span>
            </a>
            @endif
        </footer>
    </article>

    @if(count($toc) > 0)
    <aside class="docs-toc-desktop docs-toc hidden xl:block" aria-label="فهرس المحتوى">
        <p class="font-semibold mb-3" style="color:var(--docs-accent-dark)">في هذه الصفحة</p>
        @foreach($toc as $item)
        <a href="#{{ $item['id'] }}" class="level-{{ $item['level'] }}">{{ $item['text'] }}</a>
        @endforeach
    </aside>
    @endif
</div>
@endsection
