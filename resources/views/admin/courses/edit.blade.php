@extends('layouts.admin')

@section('title', 'تعديل كورس')

@section('content')
<h1 class="text-2xl font-bold text-thinkra-navy mb-6">تعديل: {{ $course->title }}</h1>
@include('components.alert')

<form method="POST" action="{{ route('admin.courses.update', $course) }}" class="max-w-2xl space-y-5 bg-white rounded-2xl border p-6 shadow-sm">
    @csrf @method('PUT')
    <div>
        <label class="block text-sm font-semibold mb-1">العنوان</label>
        <input name="title" value="{{ old('title', $course->title) }}" required class="w-full rounded-xl border px-4 py-2.5">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">الوصف</label>
        <textarea name="description" rows="3" class="w-full rounded-xl border px-4 py-2.5">{{ old('description', $course->description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-semibold mb-2">الأعضاء</label>
        <div class="space-y-2 border rounded-xl p-3 max-h-60 overflow-y-auto">
            @foreach($teachers as $teacher)
            @php $role = old("members.{$teacher->id}", $assigned[$teacher->id] ?? '') @endphp
            <div class="flex items-center gap-3 text-sm">
                <span class="flex-1">{{ $teacher->name }}</span>
                <select name="members[{{ $teacher->id }}]" class="rounded border text-xs py-1">
                    <option value="">—</option>
                    @foreach(['OWNER','EDITOR','VIEWER'] as $r)
                    <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
                    @endforeach
                </select>
            </div>
            @endforeach
        </div>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-xl bg-thinkra-purple text-white px-6 py-2.5 font-semibold">حفظ</button>
        <a href="{{ route('admin.courses.members.edit', $course) }}" class="rounded-xl border px-6 py-2.5 text-sm">إدارة الأعضاء</a>
    </div>
</form>
@endsection
