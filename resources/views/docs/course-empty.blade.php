@extends('layouts.docs')

@section('title', $course->title)

@section('content')
<div class="docs-article text-center py-16">
    <h1 class="text-2xl font-bold mb-4">{{ $course->title }}</h1>
    @if($course->description)
    <p class="opacity-70 mb-6 max-w-lg mx-auto">{{ $course->description }}</p>
    @endif
    <p class="opacity-60">لا توجد دروس منشورة للعرض حالياً.</p>
</div>
@endsection
