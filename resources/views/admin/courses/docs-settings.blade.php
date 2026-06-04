@extends('layouts.admin')

@section('title', 'إعدادات النشر — '.$course->title)

@section('content')
<a href="{{ route('admin.courses.show', $course) }}" class="text-sm text-thinkra-purple hover:underline">← {{ $course->title }}</a>
<h1 class="text-2xl font-bold text-thinkra-navy mt-4 mb-6">إعدادات النشر والمشاركة</h1>
@include('components.alert')

@if(session('share_url'))
<div class="thinkra-card p-4 mb-6 border-green-200 bg-green-50">
    <p class="text-sm font-semibold text-green-800 mb-2">رابط المشاركة الجديد:</p>
    <code class="text-xs break-all block mb-2">{{ session('share_url') }}</code>
    <p class="text-xs text-slate-600">انسخ الرابط وشاركه. يعمل مع الدورات الخاصة وغير المدرجة.</p>
</div>
@endif

<div class="grid lg:grid-cols-2 gap-6">
    <form method="POST" action="{{ route('admin.courses.docs.update', $course) }}" class="thinkra-card p-6 space-y-5">
        @csrf @method('PUT')
        <h2 class="font-semibold text-thinkra-navy">إعدادات الظهور</h2>
        <div>
            <label class="block text-sm font-semibold mb-2">الرؤية (Visibility)</label>
            <select name="visibility" class="w-full rounded-xl border px-4 py-2.5">
                @foreach(\App\Enums\CourseVisibility::cases() as $vis)
                <option value="{{ $vis->value }}" @selected(old('visibility', $course->visibility->value) === $vis->value)>{{ $vis->label() }} ({{ $vis->value }})</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-2">
                <strong>خاص:</strong> الأعضاء والإدارة فقط.
                <strong>غير مدرج:</strong> من يملك الرابط.
                <strong>عام:</strong> للجميع ويظهر بالبحث.
            </p>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="require_login" value="1" @checked(old('require_login', $course->require_login))>
            يتطلب تسجيل الدخول قبل عرض المحتوى
        </label>
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="thinkra-btn-primary px-6 py-2.5">حفظ الإعدادات</button>
            @if($course->slug)
            <a href="{{ route('docs.preview.course', $course->slug) }}" target="_blank" rel="noopener" class="rounded-xl border px-6 py-2.5 text-sm hover:bg-slate-50">معاينة القارئ</a>
            <a href="{{ route('docs.course', $course->slug) }}" target="_blank" rel="noopener" class="rounded-xl border px-6 py-2.5 text-sm hover:bg-slate-50">فتح الرابط العام</a>
            @endif
        </div>
    </form>

    <div class="thinkra-card p-6">
        <h2 class="font-semibold text-thinkra-navy mb-4">روابط المشاركة</h2>
        <form method="POST" action="{{ route('admin.courses.share-links.store', ['courseId' => $course->id]) }}" class="flex flex-wrap gap-2 mb-6">
            @csrf
            <select name="expires" class="rounded-xl border text-sm py-2 px-3 flex-1 min-w-[140px]">
                <option value="1">يوم واحد</option>
                <option value="7">7 أيام</option>
                <option value="30">30 يوماً</option>
                <option value="never">بدون انتهاء</option>
            </select>
            <button type="submit" class="thinkra-btn-primary px-4 py-2 text-sm">إنشاء رابط</button>
        </form>

        @if($course->shareLinks->isEmpty())
        <p class="text-sm text-slate-500">لا توجد روابط مشاركة.</p>
        @else
        <ul class="space-y-3 text-sm">
            @foreach($course->shareLinks as $link)
            <li class="border rounded-xl p-3">
                <code class="text-xs break-all block">{{ route('share.show', $link->token) }}</code>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $link->expires_at ? 'ينتهي: '.$link->expires_at->format('Y-m-d H:i') : 'بدون انتهاء' }}
                    — {{ $link->isValid() ? 'صالح' : 'منتهي' }}
                </p>
                <form method="POST" action="{{ route('admin.courses.share-links.destroy', ['courseId' => $course->id, 'shareLinkId' => $link->id]) }}" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 text-xs hover:underline">حذف</button>
                </form>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
</div>

<p class="text-xs text-slate-500 mt-6">مسار الدورة العام: <code>/docs/{{ $course->slug }}</code></p>
@endsection
