@extends('layouts.admin')

@section('title', 'الأساتذة')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-thinkra-navy">الأساتذة</h1>
    <a href="{{ route('admin.teachers.create') }}" class="rounded-xl bg-thinkra-purple text-white px-4 py-2 text-sm font-semibold">إضافة أستاذ</a>
</div>
@include('components.alert')

<div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="text-right p-4">الاسم</th>
                <th class="text-right p-4">الهاتف</th>
                <th class="text-right p-4">الكورسات</th>
                <th class="text-right p-4">الحالة</th>
                <th class="text-right p-4">إجراءات</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teachers as $teacher)
            <tr class="border-t">
                <td class="p-4 font-medium">{{ $teacher->name }}</td>
                <td class="p-4" dir="ltr">{{ $teacher->phone }}</td>
                <td class="p-4">{{ $teacher->courses->count() }}</td>
                <td class="p-4">
                    <span class="px-2 py-1 rounded-full text-xs {{ $teacher->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                        {{ $teacher->is_active ? 'نشط' : 'معطّل' }}
                    </span>
                </td>
                <td class="p-4 space-x-2 space-x-reverse">
                    <a href="{{ route('admin.teachers.edit', $teacher) }}" class="text-thinkra-purple hover:underline">تعديل</a>
                    <form method="POST" action="{{ route('admin.teachers.toggle', $teacher) }}" class="inline">
                        @csrf @method('PATCH')
                        <button type="submit" class="text-slate-500 hover:underline">{{ $teacher->is_active ? 'تعطيل' : 'تفعيل' }}</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="p-8 text-center text-slate-400">لا يوجد أساتذة بعد</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $teachers->links() }}</div>
@endsection
