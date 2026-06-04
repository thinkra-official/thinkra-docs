@extends('layouts.app')

@section('title', 'غير مصرح')

@section('body')
<div class="min-h-screen flex items-center justify-center p-6 bg-[#f6f7fb]">
    <div class="bg-white rounded-2xl shadow-sm border max-w-md w-full p-8 text-center">
        <p class="text-5xl font-bold text-thinkra-purple mb-2">403</p>
        <h1 class="text-xl font-bold text-thinkra-navy mb-3">غير مصرح</h1>
        <p class="text-slate-600 text-sm mb-6">{{ $exception->getMessage() ?: 'غير مصرح لك بهذا الإجراء.' }}</p>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}"
           class="thinkra-btn-primary inline-block px-6 py-2.5 text-sm">رجوع</a>
    </div>
</div>
@endsection
