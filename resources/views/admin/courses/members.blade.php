@extends('layouts.admin')

@section('title', 'أعضاء الكورس')

@section('content')
<h1 class="text-2xl font-bold text-thinkra-navy mb-2">أعضاء: {{ $course->title }}</h1>
@include('components.alert')

<form method="POST" action="{{ route('admin.courses.members.update', $course) }}" class="max-w-2xl bg-white rounded-2xl border p-6 shadow-sm">
    @csrf @method('PUT')
    <div class="space-y-3">
        @foreach($teachers as $teacher)
        @php $role = old("members.{$teacher->id}", $assigned[$teacher->id] ?? '') @endphp
        <div class="flex items-center gap-3 text-sm border-b pb-3">
            <span class="flex-1 font-medium">{{ $teacher->name }}</span>
            <select name="members[{{ $teacher->id }}]" class="rounded-xl border px-3 py-1.5">
                <option value="">غير مرتبط</option>
                @foreach(['OWNER','EDITOR','VIEWER'] as $r)
                <option value="{{ $r }}" @selected($role === $r)>{{ $r }}</option>
                @endforeach
            </select>
        </div>
        @endforeach
    </div>
    <button type="submit" class="mt-6 rounded-xl bg-thinkra-purple text-white px-6 py-2.5 font-semibold">حفظ</button>
</form>
@endsection
