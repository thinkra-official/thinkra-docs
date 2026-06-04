@extends('layouts.admin')

@section('title', 'الكورسات')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-thinkra-navy">الكورسات</h1>
    <a href="{{ route('admin.courses.create') }}" class="rounded-xl bg-thinkra-purple text-white px-4 py-2 text-sm font-semibold">كورس جديد</a>
</div>
@include('components.alert')

<div class="grid gap-4">
    @forelse($courses as $course)
    <div class="bg-white rounded-2xl border p-5 shadow-sm flex items-center justify-between">
        <div>
            <h2 class="font-bold text-lg">{{ $course->title }}</h2>
            <p class="text-sm text-slate-500 mt-1">{{ $course->members_count }} عضو · {{ $course->sections_count }} قسم</p>
        </div>
        <div class="flex gap-3 text-sm">
            <a href="{{ route('admin.courses.show', $course) }}" class="text-thinkra-navy hover:underline">عرض</a>
            <a href="{{ route('admin.courses.edit', $course) }}" class="text-thinkra-purple hover:underline">تعديل</a>
            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" onsubmit="return confirm('حذف الكورس؟')">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-600 hover:underline">حذف</button>
            </form>
        </div>
    </div>
    @empty
    <p class="text-slate-400 text-center py-12">لا توجد كورسات</p>
    @endforelse
</div>
<div class="mt-4">{{ $courses->links() }}</div>
@endsection
