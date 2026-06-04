@php
    $isActive = fn (string ...$patterns) => collect($patterns)->contains(fn ($p) => request()->routeIs($p)) ? 'is-active' : '';
    $contentRoutes = ['admin.courses.show', 'admin.courses.lessons.*', 'admin.courses.members.*'];
@endphp

<aside
    class="thinkra-sidebar thinkra-sidebar-panel fixed inset-y-0 end-0 z-50 w-64 shrink-0 flex flex-col text-white max-lg:transition-transform max-lg:duration-300 lg:static lg:z-50 lg:translate-x-0"
    :class="sidebarOpen ? 'max-lg:translate-x-0 max-lg:pointer-events-auto' : 'max-lg:translate-x-full max-lg:pointer-events-none'"
    role="navigation"
    aria-label="قائمة الإدارة">
    <div class="p-6 border-b border-white/10">
        <a href="{{ route('admin.dashboard') }}"
           class="thinkra-sidebar-brand relative z-10"
           @click="sidebarOpen = false">
            <x-application-logo variant="light" class="h-8 w-auto" />
            <span class="text-xs text-white/50 mt-2 block">لوحة الإدارة</span>
        </a>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto relative z-10">
        <a href="{{ route('admin.dashboard') }}"
           class="thinkra-nav-link {{ $isActive('admin.dashboard') }}"
           @click="sidebarOpen = false">
            <span class="thinkra-nav-icon bg-white/10 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            </span>
            الرئيسية
        </a>

        <a href="{{ route('admin.courses.index') }}"
           class="thinkra-nav-link {{ $isActive('admin.courses.index', 'admin.courses.create', 'admin.courses.edit') }}"
           @click="sidebarOpen = false">
            <span class="thinkra-nav-icon bg-sky-500/20 text-sky-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </span>
            الكورسات
        </a>

        <a href="{{ route('admin.teachers.index') }}"
           class="thinkra-nav-link {{ $isActive('admin.teachers.*') }}"
           @click="sidebarOpen = false">
            <span class="thinkra-nav-icon bg-emerald-500/20 text-emerald-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            الأساتذة
        </a>

        <a href="{{ route('admin.courses.index') }}"
           class="thinkra-nav-link {{ $isActive(...$contentRoutes) }}"
           @click="sidebarOpen = false"
           data-nav="content">
            <span class="thinkra-nav-icon bg-amber-500/20 text-amber-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
            المحتوى
        </a>

        <span class="thinkra-nav-link opacity-50 cursor-not-allowed select-none" aria-disabled="true" tabindex="-1">
            <span class="thinkra-nav-icon bg-white/5 text-white/60">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            الإعدادات
        </span>
    </nav>

    <form method="POST" action="{{ route('admin.logout') }}" class="p-4 border-t border-white/10 relative z-10">
        @csrf
        <button type="submit" class="thinkra-nav-link w-full hover:bg-red-500/20 hover:text-red-100">
            <span class="thinkra-nav-icon bg-red-500/15 text-red-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </span>
            تسجيل الخروج
        </button>
    </form>
</aside>
