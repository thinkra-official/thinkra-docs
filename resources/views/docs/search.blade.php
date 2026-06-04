@extends('layouts.docs')

@section('title', 'بحث — Thinkra Docs')

@section('content')
<div class="docs-article max-w-3xl">
    <h1 class="text-2xl font-bold mb-6" style="color:var(--docs-accent-dark)">بحث في الوثائق</h1>
    <p class="text-sm mb-4 opacity-70">البحث يشمل الدورات العامة والدروس المنشورة فقط.</p>

    <form method="GET" action="{{ route('docs.search') }}" class="mb-8">
        <div class="flex gap-2">
            <input type="search" name="q" value="{{ $query }}" placeholder="ابحث عن درس، فصل، أو دورة..."
                   class="flex-1 rounded-xl border px-4 py-3 text-base" style="border-color:var(--docs-border);background:var(--docs-bg)">
            <button type="submit" class="rounded-xl px-6 py-3 text-white font-semibold shrink-0" style="background:#9e27b5">بحث</button>
        </div>
    </form>

    @if($query !== '' && $results->isEmpty())
    <p class="opacity-70">لا توجد نتائج لـ «{{ $query }}».</p>
    @endif

    @if($results->isNotEmpty())
    <ul class="space-y-4">
        @foreach($results as $result)
        <li class="rounded-xl border p-4" style="border-color:var(--docs-border)">
            <span class="text-xs uppercase tracking-wide opacity-50">{{ $result['type'] }}</span>
            <a href="{{ $result['url'] }}" class="block font-semibold mt-1 hover:underline" style="color:#9e27b5">{{ $result['title'] }}</a>
            @if($result['excerpt'])
            <p class="text-sm mt-2 opacity-70">{{ $result['excerpt'] }}</p>
            @endif
        </li>
        @endforeach
    </ul>
    @endif
</div>
@endsection
