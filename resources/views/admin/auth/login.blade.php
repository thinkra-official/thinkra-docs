@extends('layouts.app')

@section('title', 'دخول الإدارة — Thinkra Docs')

@section('body')
<div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-thinkra-navy to-thinkra-purple/30">
    <div class="w-full max-w-md">
        <div class="flex flex-col items-center mb-8 text-white auth-logo-wrap">
            <x-application-logo variant="light" />
            <p class="text-white/80 mt-4 text-sm">لوحة الإدارة</p>
        </div>
        <div class="bg-white rounded-2xl shadow-xl p-8">
            @include('components.alert')
            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold mb-1">البريد الإلكتروني</label>
                    <input type="email" name="email" value="{{ old('email') }}" required dir="ltr"
                           class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-thinkra-purple focus:ring-2 focus:ring-thinkra-purple/20 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">كلمة المرور</label>
                    <input type="password" name="password" required
                           class="w-full rounded-xl border border-slate-200 px-4 py-3 focus:border-thinkra-purple focus:ring-2 focus:ring-thinkra-purple/20 outline-none">
                </div>
                <button type="submit" class="w-full rounded-xl bg-thinkra-navy text-white py-3 font-semibold hover:opacity-90">
                    دخول الإدارة
                </button>
            </form>
        </div>
        <p class="text-center text-xs text-white/70 mt-6">
            <a href="{{ route('teacher.login') }}" class="hover:underline">دخول الأستاذ</a>
        </p>
    </div>
</div>
@endsection
