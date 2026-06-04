@php
    $url = ($preview ?? false)
        ? route('docs.preview.lesson', [$course->slug, $lesson->slug])
        : route('docs.lesson', [$course->slug, $lesson->slug]);
    $active = isset($currentLesson) && $currentLesson && $currentLesson->id === $lesson->id;
    $completed = $isCompleted ?? false;
@endphp
<a href="{{ $url }}"
   class="course-sidebar-lesson {{ ($indented ?? false) ? 'is-indented' : '' }} {{ $active ? 'is-active' : '' }} {{ $completed ? 'is-completed' : '' }}"
   data-lesson-id="{{ $lesson->id }}"
   @if($active) aria-current="page" @endif>
    <span class="course-sidebar-lesson-icon" aria-hidden="true">
        @if($completed)
        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
        @elseif($active)
        <span class="course-sidebar-lesson-dot is-current"></span>
        @else
        <span class="course-sidebar-lesson-dot"></span>
        @endif
    </span>
    <span class="course-sidebar-lesson-text">{{ $lesson->title }}</span>
</a>
