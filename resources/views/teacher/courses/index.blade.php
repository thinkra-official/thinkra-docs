@extends('layouts.teacher')

@section('title', 'كورساتي')
@section('page-title', 'كورساتي')
@section('page-subtitle', 'الكورسات المرتبطة بحسابك')

@section('content')
@include('components.alert')

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-slate-500">{{ $courses->count() }} كورس</p>
    <a href="{{ route('teacher.dashboard') }}" class="text-sm text-thinkra-purple font-semibold hover:underline">← الرئيسية</a>
</div>

@include('teacher.courses._cards', ['courses' => $courses])
@endsection
