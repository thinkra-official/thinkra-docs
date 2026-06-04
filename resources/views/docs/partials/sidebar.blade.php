<nav class="docs-sidebar" aria-label="محتوى الدورة">
    <p class="font-bold text-white text-base mb-4">{{ $course->title }}</p>

    @foreach($sections as $section)
    <p class="section-title">{{ $section->title }}</p>

    @foreach($section->directLessons as $lesson)
    @php
        $url = ($preview ?? false)
            ? route('docs.preview.lesson', [$course->slug, $lesson->slug])
            : route('docs.lesson', [$course->slug, $lesson->slug]);
        $active = isset($currentLesson) && $currentLesson->id === $lesson->id;
    @endphp
    <a href="{{ $url }}" class="lesson-link {{ $active ? 'is-active' : '' }}">{{ $lesson->title }}</a>
    @endforeach

    @foreach($section->subSections as $subSection)
    <p class="sub-label">قسم: {{ $subSection->title }}</p>
    @foreach($subSection->lessons as $lesson)
    @php
        $url = ($preview ?? false)
            ? route('docs.preview.lesson', [$course->slug, $lesson->slug])
            : route('docs.lesson', [$course->slug, $lesson->slug]);
        $active = isset($currentLesson) && $currentLesson->id === $lesson->id;
    @endphp
    <a href="{{ $url }}" class="lesson-link {{ $active ? 'is-active' : '' }}">{{ $lesson->title }}</a>
    @endforeach
    @endforeach
    @endforeach
</nav>
