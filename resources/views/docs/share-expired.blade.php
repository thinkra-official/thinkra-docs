@extends('layouts.docs')

@section('title', 'انتهت صلاحية الرابط')

@section('content')
<div class="docs-article py-20">
    <h1 class="text-2xl font-bold mb-4">الرابط منتهي الصلاحية</h1>
    <p class="opacity-70 max-w-md mx-auto">انتهت صلاحية رابط المشاركة هذا. يرجى طلب رابط جديد من مسؤول الدورة.</p>
    <a href="{{ route('docs.search') }}" class="docs-standalone-btn">العودة للبحث</a>
</div>
@endsection
