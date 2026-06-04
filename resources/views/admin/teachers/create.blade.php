@extends('layouts.admin')

@section('title', 'إضافة أستاذ')

@section('content')
<h1 class="text-2xl font-bold text-thinkra-navy mb-6">إضافة أستاذ جديد</h1>
@include('components.alert')

<form method="POST" action="{{ route('admin.teachers.store') }}" class="max-w-2xl space-y-5 bg-white rounded-2xl border p-6 shadow-sm">
    @csrf
    <div>
        <label class="block text-sm font-semibold mb-1">الاسم</label>
        <input name="name" value="{{ old('name') }}" required class="w-full rounded-xl border px-4 py-2.5">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">رقم الهاتف</label>
        <input name="phone" value="{{ old('phone') }}" required dir="ltr" class="w-full rounded-xl border px-4 py-2.5">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">كلمة المرور</label>
        <input type="password" name="password" required class="w-full rounded-xl border px-4 py-2.5">
    </div>

    <div>
        <label class="block text-sm font-semibold mb-2">ربط بالكورسات</label>
        <div class="space-y-2 max-h-60 overflow-y-auto border rounded-xl p-3">
            @foreach($courses as $course)
            <div class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" id="c{{ $course->id }}"
                       @checked(in_array($course->id, old('course_ids', [])))>
                <label for="c{{ $course->id }}" class="flex-1">{{ $course->title }}</label>
                <select name="course_roles[{{ $course->id }}]" class="rounded border text-xs py-1">
                    <option value="OWNER">Owner</option>
                    <option value="EDITOR" selected>Editor</option>
                    <option value="VIEWER">Viewer</option>
                </select>
            </div>
            @endforeach
        </div>
    </div>

    <button type="submit" class="rounded-xl bg-thinkra-purple text-white px-6 py-2.5 font-semibold">حفظ</button>
</form>
@endsection
