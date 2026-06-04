@extends('layouts.admin')

@section('title', $course->title)
@section('page-title', $course->title)
@section('page-subtitle', 'إدارة محتوى الكورس')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
    <a href="{{ route('admin.courses.index') }}" class="text-sm text-thinkra-purple hover:underline">← الكورسات</a>
    <div class="flex flex-wrap gap-2 text-sm">
        <a href="{{ route('admin.courses.edit', $course) }}" class="rounded-lg border px-4 py-2 hover:bg-slate-50">تعديل الكورس</a>
        <a href="{{ route('admin.courses.docs.edit', $course) }}" class="rounded-lg border px-4 py-2 hover:bg-slate-50">إعدادات النشر</a>
        <a href="{{ route('admin.courses.members.edit', $course) }}" class="thinkra-btn-secondary px-4 py-2">الأعضاء</a>
        @if($course->slug)
        <a href="{{ route('docs.preview.course', $course->slug) }}" target="_blank" rel="noopener" class="rounded-lg bg-thinkra-purple text-white px-4 py-2 hover:opacity-90">معاينة</a>
        @endif
    </div>
</div>
@include('components.alert')

<div class="thinkra-card p-4 mb-6">
    <h2 class="font-semibold text-thinkra-navy mb-2">أعضاء الكورس</h2>
    <ul class="text-sm space-y-1">
        @foreach($course->members as $member)
        <li>{{ $member->name }} — <span class="text-thinkra-purple">{{ $member->pivot->role }}</span></li>
        @endforeach
    </ul>
</div>

@include('courses._structure', [
    'course' => $course,
    'context' => 'admin',
    'canManage' => true,
    'canEditContent' => true,
])
@endsection
