@extends('layouts.app')

@section('title', 'غير موجود')

@section('body')
<div class="min-h-screen flex items-center justify-center p-6 bg-[#f6f7fb]">
    <div class="bg-white rounded-2xl shadow-sm border max-w-md w-full p-8 text-center">
        <p class="text-5xl font-bold text-thinkra-purple mb-2">404</p>
        <h1 class="text-xl font-bold text-thinkra-navy mb-3">الصفحة غير موجودة</h1>
        <p class="text-slate-600 text-sm mb-6">{{ $exception->getMessage() ?: 'الصفحة أو العنصر غير موجود.' }}</p>
        <a href="{{ route('home') }}" class="thinkra-btn-primary inline-block px-6 py-2.5 text-sm">الرئيسية</a>
    </div>
</div>
@endsection
