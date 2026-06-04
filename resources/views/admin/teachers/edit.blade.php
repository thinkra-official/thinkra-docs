@extends('layouts.admin')

@section('title', 'تعديل أستاذ')

@section('content')
<h1 class="text-2xl font-bold text-thinkra-navy mb-6">تعديل: {{ $teacher->name }}</h1>
@include('components.alert')

<form method="POST" action="{{ route('admin.teachers.update', $teacher) }}" class="max-w-2xl space-y-5 bg-white rounded-2xl border p-6 shadow-sm">
    @csrf @method('PUT')
    <div>
        <label class="block text-sm font-semibold mb-1">الاسم</label>
        <input name="name" value="{{ old('name', $teacher->name) }}" required class="w-full rounded-xl border px-4 py-2.5">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">رقم الهاتف</label>
        <input name="phone" value="{{ old('phone', $teacher->phone) }}" required dir="ltr" class="w-full rounded-xl border px-4 py-2.5">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">كلمة مرور جديدة (اختياري)</label>
        <input type="password" name="password" class="w-full rounded-xl border px-4 py-2.5">
    </div>
    <div>
        <label class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $teacher->is_active))>
            <span class="text-sm font-semibold">الحساب نشط</span>
        </label>
    </div>

    <div>
        <label class="block text-sm font-semibold mb-2">الكورسات</label>
        <div class="space-y-2 max-h-60 overflow-y-auto border rounded-xl p-3">
            @foreach($courses as $course)
            @php $role = old("course_roles.{$course->id}", $assigned[$course->id] ?? '') @endphp
            <div class="flex items-center gap-3 text-sm">
                <input type="checkbox" name="course_ids[]" value="{{ $course->id }}" id="c{{ $course->id }}"
                       @checked(in_array($course->id, old('course_ids', $assigned->keys()->toArray())))>
                <label for="c{{ $course->id }}" class="flex-1">{{ $course->title }}</label>
                <select name="course_roles[{{ $course->id }}]" class="rounded border text-xs py-1">
                    @foreach(['OWNER','EDITOR','VIEWER'] as $r)
                    <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            @endforeach
        </div>
    </div>

    <button type="submit" class="rounded-xl bg-thinkra-purple text-white px-6 py-2.5 font-semibold">تحديث</button>
</form>
@endsection
