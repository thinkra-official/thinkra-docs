@extends('layouts.app')

@section('title', 'دخول الأستاذ — Thinkra Docs')

@section('body')
<div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="flex flex-col items-center mb-8">
            <x-application-logo class="h-10 w-auto sm:h-11" />
            <p class="text-slate-500 mt-4 text-sm">تسجيل دخول الأستاذ برقم الهاتف</p>
        </div>
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 p-8">
            @include('components.alert')
            <form method="POST" action="{{ route('teacher.login.submit') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">رقم الهاتف</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" required
                           class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-thinkra-purple focus:ring-2 focus:ring-thinkra-purple/20 outline-none"
                           placeholder="05xxxxxxxx" dir="ltr">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">كلمة المرور</label>
                    <input type="password" name="password" required
                           class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-thinkra-purple focus:ring-2 focus:ring-thinkra-purple/20 outline-none">
                    <p class="text-xs text-slate-400 mt-1">تُعيَّن من الإدارة عند إنشاء الحساب</p>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-thinkra-purple">
                    تذكرني
                </label>
                <button type="submit" class="w-full rounded-xl bg-thinkra-purple text-white py-3 font-semibold hover:opacity-90 transition">
                    دخول
                </button>
            </form>
        </div>
        <p class="text-center text-xs text-slate-400 mt-6">
            <a href="{{ route('admin.login') }}" class="text-thinkra-navy hover:underline">دخول الإدارة</a>
        </p>
    </div>
</div>
@endsection
