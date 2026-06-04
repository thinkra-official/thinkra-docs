@php
    $completedSet = array_flip($completedLessonIds ?? []);
    $progress = $progressStats ?? ['total' => 0, 'completed' => 0, 'percent' => 0, 'remaining' => 0];
    $expandedSectionIds = $expandedSections ?? [];
    $expandedSubIds = $expandedSubSections ?? [];
@endphp

<nav class="course-sidebar"
     x-data="{
        openSections: @js($expandedSectionIds),
        openSubs: @js($expandedSubIds),
        toggleSection(id) {
            if (this.openSections.includes(id)) {
                this.openSections = this.openSections.filter(s => s !== id);
            } else {
                this.openSections.push(id);
            }
        },
        toggleSub(id) {
            if (this.openSubs.includes(id)) {
                this.openSubs = this.openSubs.filter(s => s !== id);
            } else {
                this.openSubs.push(id);
            }
        },
        sectionOpen(id) { return this.openSections.includes(id); },
        subOpen(id) { return this.openSubs.includes(id); }
     }"
     aria-label="محتوى الدورة">

    @unless($hideHeader ?? false)
    <div class="course-sidebar-header">
        <a href="{{ ($preview ?? false) ? route('docs.preview.course', $course->slug) : route('docs.course', $course->slug) }}"
           class="course-sidebar-title">{{ $course->title }}</a>
        @if(($progress['total'] ?? 0) > 0)
        <div class="course-sidebar-progress">
            <div class="course-sidebar-progress-meta">
                <span>{{ $progress['percent'] }}% مكتمل</span>
                <span>{{ $progress['completed'] }}/{{ $progress['total'] }}</span>
            </div>
            <div class="course-sidebar-progress-track" role="progressbar"
                 aria-valuenow="{{ $progress['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                <div class="course-sidebar-progress-fill" style="width: {{ $progress['percent'] }}%"></div>
            </div>
        </div>
        @endif
    </div>
    @endunless

    <div class="course-sidebar-tree">
        @foreach($sections as $section)
        @php $sectionOpen = in_array($section->id, $expandedSectionIds, true); @endphp
        <div class="course-sidebar-section" data-section-id="{{ $section->id }}">
            <button type="button"
                    class="course-sidebar-section-btn"
                    @click="toggleSection({{ $section->id }})"
                    :aria-expanded="sectionOpen({{ $section->id }})">
                <span class="course-sidebar-chevron" :class="{ 'is-open': sectionOpen({{ $section->id }}) }" aria-hidden="true">▸</span>
                <span class="course-sidebar-section-label">{{ $section->title }}</span>
            </button>

            <div class="course-sidebar-section-body" x-show="sectionOpen({{ $section->id }})" @if(!$sectionOpen) style="display: none" @endif>
                @foreach($section->directLessons as $lesson)
                @include('docs.partials.sidebar-lesson-link', [
                    'lesson' => $lesson,
                    'course' => $course,
                    'preview' => $preview,
                    'currentLesson' => $currentLesson,
                    'isCompleted' => isset($completedSet[$lesson->id]),
                ])
                @endforeach

                @foreach($section->subSections as $subSection)
                @php $subOpen = in_array($subSection->id, $expandedSubIds, true); @endphp
                <div class="course-sidebar-subsection" data-subsection-id="{{ $subSection->id }}">
                    <button type="button"
                            class="course-sidebar-sub-btn"
                            @click="toggleSub({{ $subSection->id }})"
                            :aria-expanded="subOpen({{ $subSection->id }})">
                        <span class="course-sidebar-chevron is-sub" :class="{ 'is-open': subOpen({{ $subSection->id }}) }" aria-hidden="true">▸</span>
                        <span>{{ $subSection->title }}</span>
                    </button>
                    <div class="course-sidebar-sub-body" x-show="subOpen({{ $subSection->id }})" @if(!$subOpen) style="display: none" @endif>
                        @foreach($subSection->lessons as $lesson)
                        @include('docs.partials.sidebar-lesson-link', [
                            'lesson' => $lesson,
                            'course' => $course,
                            'preview' => $preview,
                            'currentLesson' => $currentLesson,
                            'isCompleted' => isset($completedSet[$lesson->id]),
                            'indented' => true,
                        ])
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</nav>
