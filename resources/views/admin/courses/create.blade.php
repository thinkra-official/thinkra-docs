@extends('layouts.admin')

@section('title', 'كورس جديد')

@section('content')
<h1 class="text-2xl font-bold text-thinkra-navy mb-6">إنشاء كورس</h1>
@include('components.alert')

<form method="POST" action="{{ route('admin.courses.store') }}" class="max-w-2xl space-y-5 bg-white rounded-2xl border p-6 shadow-sm">
    @csrf
    <div>
        <label class="block text-sm font-semibold mb-1">عنوان الكورس</label>
        <input name="title" value="{{ old('title') }}" required class="w-full rounded-xl border px-4 py-2.5">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">الوصف</label>
        <textarea name="description" rows="3" class="w-full rounded-xl border px-4 py-2.5">{{ old('description') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-semibold mb-2">الأساتذة والصلاحيات</label>
        <div class="space-y-2 border rounded-xl p-3 max-h-60 overflow-y-auto">
            @foreach($teachers as $teacher)
            <div class="flex items-center gap-3 text-sm">
                <span class="flex-1">{{ $teacher->name }} <span class="text-slate-400" dir="ltr">({{ $teacher->phone }})</span></span>
                <select name="members[{{ $teacher->id }}]" class="rounded border text-xs py-1">
                    <option value="">—</option>
                    <option value="OWNER">Owner</option>
                    <option value="EDITOR">Editor</option>
                    <option value="VIEWER">Viewer</option>
                </select>
            </div>
            @endforeach
        </div>
    </div>
    <button type="submit" class="rounded-xl bg-thinkra-purple text-white px-6 py-2.5 font-semibold">إنشاء</button>
</form>
@endsection
