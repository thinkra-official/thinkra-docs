@extends('layouts.docs')

@section('title', 'انتهت صلاحية الرابط')

@section('content')
<div class="docs-article text-center py-20">
    <h1 class="text-2xl font-bold mb-4" style="color:#0f206c">الرابط منتهي الصلاحية</h1>
    <p class="opacity-70 max-w-md mx-auto">انتهت صلاحية رابط المشاركة هذا. يرجى طلب رابط جديد من مسؤول الدورة.</p>
    <a href="{{ route('docs.search') }}" class="inline-block mt-8 px-6 py-3 rounded-xl text-white font-semibold" style="background:#9e27b5">العودة للبحث</a>
</div>
@endsection
