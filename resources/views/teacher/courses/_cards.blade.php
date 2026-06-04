@if($courses->isEmpty())
<div class="thinkra-card p-12 text-center text-slate-400">
    لا توجد كورسات مرتبطة بحسابك بعد.
</div>
@else
<div class="grid md:grid-cols-2 gap-4 lg:gap-6">
    @foreach($courses as $course)
    @php $role = app(\App\Services\CourseAccessService::class)->memberRole(auth()->user(), $course) @endphp
    @php
        $lessonCount = $course->sections->sum(
            fn ($section) => $section->subSections->sum(fn ($sub) => $sub->lessons->count())
        );
    @endphp
    <a href="{{ route('teacher.courses.show', ['courseId' => $course->id]) }}"
       class="thinkra-card block p-6 hover:shadow-lg hover:border-thinkra-purple/20 transition group">
        <div class="flex items-start gap-4">
            <div class="thinkra-stat-icon bg-violet-100 text-thinkra-purple shrink-0 group-hover:scale-105 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="font-bold text-lg text-thinkra-navy group-hover:text-thinkra-purple transition">{{ $course->title }}</h3>
                <p class="text-sm text-slate-500 mt-2 line-clamp-2">{{ $course->description }}</p>
                <div class="mt-4 flex items-center justify-between text-xs">
                    <span class="px-2.5 py-1 rounded-full bg-thinkra-purple/10 text-thinkra-purple font-semibold">{{ $role?->label() }}</span>
                    <span class="text-slate-400">{{ $lessonCount }} درس</span>
                </div>
            </div>
        </div>
    </a>
    @endforeach
</div>
@endif
