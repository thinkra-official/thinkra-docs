@extends('layouts.app')

@section('body')
<div class="thinkra-dashboard min-h-screen flex flex-row-reverse bg-[#f6f7fb]" x-data="{ sidebarOpen: false }">
    @yield('sidebar')

    <div x-show="sidebarOpen"
         x-cloak
         x-transition.opacity
         class="fixed inset-0 bg-thinkra-navy/40 z-40 lg:hidden"
         @click="sidebarOpen = false"
         aria-hidden="true"></div>

    <div class="flex-1 flex flex-col min-w-0 relative z-0">
        <header class="thinkra-topbar sticky top-0 z-20 px-4 lg:px-8 py-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button"
                        class="lg:hidden w-10 h-10 rounded-xl border border-slate-200 flex items-center justify-center text-thinkra-navy shrink-0"
                        @click="sidebarOpen = !sidebarOpen"
                        aria-label="القائمة">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                @php
                    $headerLogoUrl = request()->routeIs('teacher.*')
                        ? route('teacher.dashboard')
                        : route('admin.dashboard');
                @endphp
                <a href="{{ $headerLogoUrl }}" class="thinkra-header-logo shrink-0" aria-label="THINKRA">
                    <x-application-logo />
                </a>
                <div class="min-w-0">
                    <h1 class="text-lg font-bold text-thinkra-navy truncate">@yield('page-title', 'Thinkra Docs')</h1>
                    @hasSection('page-subtitle')
                    <p class="text-xs text-slate-500 truncate">@yield('page-subtitle')</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <div class="hidden sm:flex items-center gap-2 text-sm text-slate-600">
                    <span class="w-9 h-9 rounded-full bg-thinkra-purple/10 text-thinkra-purple font-bold flex items-center justify-center">
                        {{ mb_substr(auth()->user()->name, 0, 1) }}
                    </span>
                    <span class="font-medium">{{ auth()->user()->name }}</span>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-8 overflow-x-hidden">
            @yield('content')
        </main>
    </div>
</div>
@endsection

@push('head')
<link rel="stylesheet" href="{{ asset('css/thinkra-dashboard.css') }}">
@endpush
