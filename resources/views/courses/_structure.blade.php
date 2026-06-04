{{--

    $course, $context ('teacher'|'admin'), $canManage, $canEditContent

--}}

@php

    use App\Support\NestedCourseRoute;

    $isTeacher = $context === 'teacher';

    $routePrefix = $isTeacher ? 'teacher.' : 'admin.courses.';

    $route = fn (string $name, array $parameters = []) => route($routePrefix.$name, $parameters);

    $routeSectionLesson = fn (string $name, array $parameters = []) => route($routePrefix.'section-lessons.'.$name, $parameters);

    $routeSubLesson = fn (string $name, array $parameters = []) => route($routePrefix.'lessons.'.$name, $parameters);

    $showUrlSection = $isTeacher

        ? fn ($course, $section, $lesson) => route('teacher.section-lessons.edit', NestedCourseRoute::sectionLesson($course, $section, $lesson))

        : fn ($course, $section, $lesson) => route('admin.courses.section-lessons.edit', NestedCourseRoute::sectionLesson($course, $section, $lesson));

    $showUrlSub = $isTeacher

        ? fn ($course, $section, $sub, $lesson) => route('teacher.lessons.edit', NestedCourseRoute::subSectionLesson($course, $section, $sub, $lesson))

        : fn ($course, $section, $sub, $lesson) => route('admin.courses.lessons.edit', NestedCourseRoute::subSectionLesson($course, $section, $sub, $lesson));

    $canReorder = $canManage || $canEditContent;

    $structureReorderUrl = $isTeacher
        ? route('teacher.courses.structure.reorder', ['courseId' => $course->id])
        : route('admin.courses.structure.reorder', ['courseId' => $course->id]);

@endphp



@if($canManage)

<div x-data="{ openSection: false }" class="mb-6">

    <button @click="openSection = !openSection" type="button" class="thinkra-btn-primary px-4 py-2 text-sm">+ إضافة فصل</button>

    <form x-show="openSection" x-cloak method="POST" action="{{ $route('sections.store', ['courseId' => $course->id]) }}" class="mt-3 flex gap-2 max-w-md">

        @csrf

        <input name="title" required placeholder="عنوان الفصل" class="flex-1 rounded-xl border px-4 py-2">

        <button type="submit" class="thinkra-btn-primary px-4 py-2 text-sm">إضافة</button>

    </form>

</div>

@endif

@if($canReorder)
<div id="course-structure-reorder"
     data-enabled="1"
     data-can-manage="{{ $canManage ? '1' : '0' }}"
     data-can-edit="{{ $canEditContent ? '1' : '0' }}"
     data-reorder-url="{{ $structureReorderUrl }}"
     class="mb-4">
    <div id="structure-reorder-status"
         class="hidden mb-3 rounded-xl border px-4 py-2 text-sm shadow-sm"
         role="status"
         aria-live="polite"></div>
    <p class="text-xs text-slate-500 mb-3">اسحب من أيقونة ⋮⋮ لإعادة الترتيب. يمكنك أيضاً استخدام أزرار ↑↓.</p>
</div>
@endif

<ul id="course-sections-sortable" class="list-none p-0 m-0 space-y-0">

@foreach($course->sections as $section)

@php

    $sectionSubs = $section->subSections->count();

    $directCount = $section->directLessons->count();

    $subLessonsCount = $section->subSections->sum(fn ($ss) => $ss->lessons->count());

    $totalLessons = $directCount + $subLessonsCount;

    $sectionDeleteMsg = "تحذير: حذف الفصل «{$section->title}» سيحذف نهائياً {$sectionSubs} قسم و {$totalLessons} درس. هل أنت متأكد؟";

    $sectionLessonStoreUrl = $isTeacher
        ? route('teacher.section-lessons.store', ['courseId' => $course->id, 'sectionId' => $section->id])
        : route('admin.courses.section-lessons.store', ['courseId' => $course->id, 'sectionId' => $section->id]);

    $subSectionStoreUrl = $isTeacher
        ? route('teacher.sub-sections.store', ['courseId' => $course->id, 'sectionId' => $section->id])
        : route('admin.courses.sub-sections.store', ['courseId' => $course->id, 'sectionId' => $section->id]);

    $sectionDestroyUrl = $isTeacher
        ? route('teacher.sections.destroy', ['courseId' => $course->id, 'sectionId' => $section->id])
        : route('admin.courses.sections.destroy', ['courseId' => $course->id, 'sectionId' => $section->id]);

    $sectionMoveUrl = $isTeacher
        ? route('teacher.sections.move', ['courseId' => $course->id, 'sectionId' => $section->id])
        : route('admin.courses.sections.move', ['courseId' => $course->id, 'sectionId' => $section->id]);

@endphp

<li class="thinkra-card mb-6 overflow-hidden list-none" data-section-id="{{ $section->id }}" data-type="section">

    <div data-section-root data-section-id="{{ $section->id }}" x-data="{ openSub: false, editSection: false }">

    <div class="bg-thinkra-navy/5 px-4 py-3 flex flex-wrap justify-between items-center gap-2 border-b">

        <div class="flex items-center gap-2 min-w-0 flex-1">

            @if($canReorder && $canManage)
            @include('courses.partials._drag-handle', ['type' => 'section'])
            @endif

            @if($canManage)

            <div class="flex flex-col gap-0.5 shrink-0 hidden sm:flex" title="ترتيب يدوي">

                <!-- move action: {{ $sectionMoveUrl }} -->
                <form method="POST" action="{{ $sectionMoveUrl }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="direction" value="up">
                    <button type="submit" class="w-7 h-6 rounded border border-slate-200 text-xs hover:bg-white" title="أعلى">↑</button>
                </form>

                <!-- move action: {{ $sectionMoveUrl }} -->
                <form method="POST" action="{{ $sectionMoveUrl }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="_method" value="PATCH">
                    <input type="hidden" name="direction" value="down">
                    <button type="submit" class="w-7 h-6 rounded border border-slate-200 text-xs hover:bg-white" title="أسفل">↓</button>
                </form>

            </div>

            @endif

            <div class="min-w-0 flex-1" x-show="!editSection">

                <h2 class="font-bold text-thinkra-navy">{{ $section->title }}</h2>

            </div>

            @if($canManage)

            <form x-show="editSection" x-cloak method="POST" action="{{ $route('sections.update', NestedCourseRoute::section($course, $section)) }}" class="flex gap-2 flex-1">

                @csrf @method('PUT')

                <input name="title" value="{{ $section->title }}" required class="flex-1 rounded-lg border px-3 py-1.5 text-sm">

                <button type="submit" class="text-xs text-thinkra-purple font-semibold">حفظ</button>

                <button type="button" @click="editSection = false" class="text-xs text-slate-400">إلغاء</button>

            </form>

            @endif

        </div>

        @if($canManage)

        <div class="flex items-center gap-2 text-xs shrink-0">

            <button type="button" @click="editSection = !editSection" class="text-thinkra-navy hover:underline">تعديل</button>

            <button @click="openSub = !openSub" type="button" class="text-thinkra-navy font-semibold">+ قسم</button>

            <!-- delete action: {{ $sectionDestroyUrl }} -->
            <form method="POST" action="{{ $sectionDestroyUrl }}" onsubmit="return confirm(@js($sectionDeleteMsg))">

                @csrf
                @method('DELETE')

                <button type="submit" class="text-red-600 hover:underline">حذف</button>

            </form>

        </div>

        @endif

    </div>



    @if($canEditContent)
    <form method="POST" action="{{ $sectionLessonStoreUrl }}" class="px-4 py-3 border-b bg-violet-50/40 flex flex-wrap gap-2 items-center">
        @csrf
        <input type="hidden" name="objective" value="">
        <input type="hidden" name="main_content" value="">
        <input type="hidden" name="teacher_notes" value="">
        <input name="title" required placeholder="عنوان الدرس" class="flex-1 min-w-[12rem] rounded-lg border px-3 py-2 text-sm">
        <button type="submit" class="thinkra-btn-primary px-4 py-2 text-sm shrink-0">إضافة درس</button>
    </form>
    @endif



    @if($canManage)

    <form x-show="openSub" x-cloak method="POST" action="{{ $subSectionStoreUrl }}" class="px-4 py-3 border-b bg-[#f6f7fb] flex gap-2">

        @csrf

        <input name="title" required placeholder="عنوان القسم" class="flex-1 rounded-lg border px-3 py-2 text-sm">

        <button type="submit" class="thinkra-btn-primary px-3 py-2 text-sm">إضافة قسم</button>

    </form>

    @endif



    <ul class="direct-lessons-sortable sortable-lessons divide-y min-h-[2.5rem]"
        data-section-id="{{ $section->id }}"
        data-lesson-container="direct">

        @foreach($section->directLessons as $lesson)

        <li class="px-4 py-3 flex flex-wrap justify-between items-center gap-2 text-sm"
            data-lesson-id="{{ $lesson->id }}"
            data-type="lesson">

            <div class="flex items-center gap-2 min-w-0 flex-1">

                @if($canReorder && $canEditContent)
                @include('courses.partials._drag-handle', ['type' => 'lesson'])
                @endif

                @if($canEditContent)

                <div class="flex flex-col gap-0.5 shrink-0 hidden sm:flex" title="ترتيب يدوي">

                    @php
                        $lessonMoveUrl = $isTeacher
                            ? route('teacher.section-lessons.move', [
                                'courseId' => $course->id,
                                'sectionId' => $section->id,
                                'lessonId' => $lesson->id,
                            ])
                            : route('admin.courses.section-lessons.move', [
                                'courseId' => $course->id,
                                'sectionId' => $section->id,
                                'lessonId' => $lesson->id,
                            ]);
                    @endphp
                    <!-- move action: {{ $lessonMoveUrl }} -->
                    <form method="POST" action="{{ $lessonMoveUrl }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_method" value="PATCH">
                        <input type="hidden" name="direction" value="up">
                        <button type="submit" class="w-7 h-6 rounded border border-slate-200 text-xs hover:bg-white">↑</button>
                    </form>

                    <!-- move action: {{ $lessonMoveUrl }} -->
                    <form method="POST" action="{{ $lessonMoveUrl }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_method" value="PATCH">
                        <input type="hidden" name="direction" value="down">
                        <button type="submit" class="w-7 h-6 rounded border border-slate-200 text-xs hover:bg-white">↓</button>
                    </form>

                </div>

                @endif

                @php $lessonShowUrl = $showUrlSection($course, $section, $lesson); @endphp
                <!-- lesson {{ $isTeacher ? 'edit' : 'show' }} url: {{ $lessonShowUrl }} -->
                <a href="{{ $lessonShowUrl }}" class="font-medium text-thinkra-navy hover:text-thinkra-purple truncate">

                    {{ $lesson->title }}

                </a>

            </div>

            <div class="flex items-center gap-3 shrink-0">

                <span class="px-2 py-0.5 rounded-full text-xs {{ $lesson->status->badgeClass() }}">{{ $lesson->status->label() }}</span>

                @if($canEditContent)

                @php
                    $lessonDeleteUrl = $isTeacher
                        ? route('teacher.section-lessons.destroy', [
                            'courseId' => $course->id,
                            'sectionId' => $section->id,
                            'lessonId' => $lesson->id,
                        ])
                        : route('admin.courses.section-lessons.destroy', [
                            'courseId' => $course->id,
                            'sectionId' => $section->id,
                            'lessonId' => $lesson->id,
                        ]);
                @endphp
                <!-- lesson delete action: {{ $lessonDeleteUrl }} -->
                <form method="POST" action="{{ $lessonDeleteUrl }}"
                      onsubmit="return confirm('هل تريد حذف الدرس «{{ $lesson->title }}» نهائياً؟')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="text-red-600 hover:underline text-xs">حذف</button>
                </form>

                @endif

            </div>

        </li>

        @endforeach

    </ul>

    <ul class="subsections-sortable list-none p-0 m-0"
        data-section-id="{{ $section->id }}">

    @foreach($section->subSections as $subSection)

    @php

        $subLessons = $subSection->lessons->count();

        $subDeleteMsg = "تحذير: حذف القسم «{$subSection->title}» سيحذف نهائياً {$subLessons} درس. هل أنت متأكد؟";

        $subLessonStoreUrl = $isTeacher
            ? route('teacher.lessons.store', [
                'courseId' => $course->id,
                'sectionId' => $section->id,
                'subSectionId' => $subSection->id,
            ])
            : route('admin.courses.lessons.store', [
                'courseId' => $course->id,
                'sectionId' => $section->id,
                'subSectionId' => $subSection->id,
            ]);

        $subSectionMoveUrl = $isTeacher
            ? route('teacher.sub-sections.move', [
                'courseId' => $course->id,
                'sectionId' => $section->id,
                'subSectionId' => $subSection->id,
            ])
            : route('admin.courses.sub-sections.move', [
                'courseId' => $course->id,
                'sectionId' => $section->id,
                'subSectionId' => $subSection->id,
            ]);

    @endphp

    <li class="border-t border-slate-100 list-none"
        data-sub-section-id="{{ $subSection->id }}"
        data-type="sub-section"
        x-data="{ editSub: false }">

        <div class="px-4 py-2.5 bg-amber-50/50 flex flex-wrap justify-between items-center gap-2">

            <div class="flex items-center gap-2 min-w-0 flex-1">

                @if($canReorder && $canManage)
                @include('courses.partials._drag-handle', ['type' => 'subsection'])
                @endif

                @if($canManage)

                <div class="flex flex-col gap-0.5 shrink-0 hidden sm:flex" title="ترتيب يدوي">

                    <!-- move action: {{ $subSectionMoveUrl }} -->
                    <form method="POST" action="{{ $subSectionMoveUrl }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_method" value="PATCH">
                        <input type="hidden" name="direction" value="up">
                        <button type="submit" class="w-7 h-6 rounded border border-slate-200 text-xs hover:bg-white">↑</button>
                    </form>

                    <!-- move action: {{ $subSectionMoveUrl }} -->
                    <form method="POST" action="{{ $subSectionMoveUrl }}" class="inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="_method" value="PATCH">
                        <input type="hidden" name="direction" value="down">
                        <button type="submit" class="w-7 h-6 rounded border border-slate-200 text-xs hover:bg-white">↓</button>
                    </form>

                </div>

                @endif

                <div x-show="!editSub" class="min-w-0">

                    <h3 class="text-sm font-semibold text-slate-700">قسم: {{ $subSection->title }}</h3>

                </div>

                @if($canManage)

                <form x-show="editSub" x-cloak method="POST" action="{{ $route('sub-sections.update', NestedCourseRoute::subSection($course, $section, $subSection)) }}" class="flex gap-2 flex-1">

                    @csrf @method('PUT')

                    <input name="title" value="{{ $subSection->title }}" required class="flex-1 rounded-lg border px-3 py-1.5 text-sm">

                    <button type="submit" class="text-xs text-thinkra-purple font-semibold">حفظ</button>

                    <button type="button" @click="editSub = false" class="text-xs text-slate-400">إلغاء</button>

                </form>

                @endif

            </div>

            <div class="flex items-center gap-2 text-xs shrink-0">

                @if($canManage)

                <button type="button" @click="editSub = !editSub" class="text-thinkra-navy hover:underline">تعديل</button>

                @php
                    $subSectionDestroyUrl = $isTeacher
                        ? route('teacher.sub-sections.destroy', ['courseId' => $course->id, 'sectionId' => $section->id, 'subSectionId' => $subSection->id])
                        : route('admin.courses.sub-sections.destroy', ['courseId' => $course->id, 'sectionId' => $section->id, 'subSectionId' => $subSection->id]);
                @endphp
                <!-- delete action: {{ $subSectionDestroyUrl }} -->
                <form method="POST" action="{{ $subSectionDestroyUrl }}" onsubmit="return confirm(@js($subDeleteMsg))">

                    @csrf
                    @method('DELETE')

                    <button type="submit" class="text-red-600 hover:underline">حذف</button>

                </form>

                @endif

            </div>

        </div>

        @if($canEditContent)
        <form method="POST" action="{{ $subLessonStoreUrl }}" class="px-4 py-3 border-b flex flex-wrap gap-2 items-center mr-4">
            @csrf
            <input type="hidden" name="objective" value="">
            <input type="hidden" name="main_content" value="">
            <input type="hidden" name="teacher_notes" value="">
            <input name="title" required placeholder="عنوان الدرس" class="flex-1 min-w-[12rem] rounded-lg border px-3 py-2 text-sm">
            <button type="submit" class="thinkra-btn-primary px-4 py-2 text-sm shrink-0">إضافة درس</button>
        </form>
        @endif



        <ul class="sub-lessons-sortable sortable-lessons divide-y mr-4 border-r-2 border-amber-200/60 min-h-[2.5rem]"
            data-section-id="{{ $section->id }}"
            data-sub-section-id="{{ $subSection->id }}"
            data-lesson-container="sub-section">

            @forelse($subSection->lessons as $lesson)

            <li class="px-4 py-3 flex flex-wrap justify-between items-center gap-2 text-sm"
                data-lesson-id="{{ $lesson->id }}"
                data-type="lesson">

                <div class="flex items-center gap-2 min-w-0 flex-1">

                    @if($canReorder && $canEditContent)
                    @include('courses.partials._drag-handle', ['type' => 'lesson'])
                    @endif

                    @if($canEditContent)

                    <div class="flex flex-col gap-0.5 shrink-0 hidden sm:flex" title="ترتيب يدوي">

                        @php
                            $lessonMoveUrl = $isTeacher
                                ? route('teacher.lessons.move', [
                                    'courseId' => $course->id,
                                    'sectionId' => $section->id,
                                    'subSectionId' => $subSection->id,
                                    'lessonId' => $lesson->id,
                                ])
                                : route('admin.courses.lessons.move', [
                                    'courseId' => $course->id,
                                    'sectionId' => $section->id,
                                    'subSectionId' => $subSection->id,
                                    'lessonId' => $lesson->id,
                                ]);
                        @endphp
                        <!-- move action: {{ $lessonMoveUrl }} -->
                        <form method="POST" action="{{ $lessonMoveUrl }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="_method" value="PATCH">
                            <input type="hidden" name="direction" value="up">
                            <button type="submit" class="w-7 h-6 rounded border border-slate-200 text-xs hover:bg-white">↑</button>
                        </form>

                        <!-- move action: {{ $lessonMoveUrl }} -->
                        <form method="POST" action="{{ $lessonMoveUrl }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="_method" value="PATCH">
                            <input type="hidden" name="direction" value="down">
                            <button type="submit" class="w-7 h-6 rounded border border-slate-200 text-xs hover:bg-white">↓</button>
                        </form>

                    </div>

                    @endif

                    @php $lessonShowUrl = $showUrlSub($course, $section, $subSection, $lesson); @endphp
                    <!-- lesson {{ $isTeacher ? 'edit' : 'show' }} url: {{ $lessonShowUrl }} -->
                    <a href="{{ $lessonShowUrl }}" class="font-medium text-thinkra-navy hover:text-thinkra-purple truncate">

                        {{ $lesson->title }}

                    </a>

                </div>

                <div class="flex items-center gap-3 shrink-0">

                    <span class="px-2 py-0.5 rounded-full text-xs {{ $lesson->status->badgeClass() }}">{{ $lesson->status->label() }}</span>

                    @if($canEditContent)

                    @php
                        $lessonDeleteUrl = $isTeacher
                            ? route('teacher.lessons.destroy', [
                                'courseId' => $course->id,
                                'sectionId' => $section->id,
                                'subSectionId' => $subSection->id,
                                'lessonId' => $lesson->id,
                            ])
                            : route('admin.courses.lessons.destroy', [
                                'courseId' => $course->id,
                                'sectionId' => $section->id,
                                'subSectionId' => $subSection->id,
                                'lessonId' => $lesson->id,
                            ]);
                    @endphp
                    <!-- lesson delete action: {{ $lessonDeleteUrl }} -->
                    <form method="POST" action="{{ $lessonDeleteUrl }}"
                          onsubmit="return confirm('هل تريد حذف الدرس «{{ $lesson->title }}» نهائياً؟')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="text-red-600 hover:underline text-xs">حذف</button>
                    </form>

                    @endif

                </div>

            </li>

            @empty

            <li class="px-4 py-4 text-center text-slate-400 text-xs">لا توجد دروس في هذا القسم</li>

            @endforelse

        </ul>

    </li>

    @endforeach

    </ul>



    @if($section->directLessons->isEmpty() && $section->subSections->isEmpty())

    <p class="px-4 py-6 text-center text-slate-400 text-sm">أضف درساً أو قسماً داخل هذا الفصل</p>

    @endif

    </div>

</li>

@endforeach

</ul>



@if($course->sections->isEmpty())

<p class="text-slate-400 text-center py-12 thinkra-card">ابدأ بإضافة فصل أولاً.</p>

@endif

@if($canReorder)
@push('head')
<style>
    .sortable-ghost { opacity: 0.45; background: #f3e8ff; }
    .sortable-chosen { background: #faf5ff; }
    .sortable-drag { cursor: grabbing; }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="{{ asset_version('js/course-structure-sortable.js') }}"></script>
@endpush
@endif

