@extends('layouts.teacher')

@section('title', $course->title)
@section('page-title', $course->title)
@section('page-subtitle', $memberRole->label())

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4 mb-2">
    <a href="{{ route('teacher.courses.index') }}" class="text-sm text-thinkra-purple hover:underline">← كورساتي</a>
    @if($course->slug)
    <a href="{{ route('docs.preview.course', $course->slug) }}" target="_blank" rel="noopener"
       class="text-sm rounded-lg bg-thinkra-purple text-white px-4 py-2 font-semibold hover:opacity-90">معاينة القارئ</a>
    @endif
</div>
@include('components.alert')

@include('courses._structure', [
    'course' => $course,
    'context' => 'teacher',
    'canManage' => $memberRole->canManageCourse(),
    'canEditContent' => $memberRole->canEditContent(),
])
@endsection
