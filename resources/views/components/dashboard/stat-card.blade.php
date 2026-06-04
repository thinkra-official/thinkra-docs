@props([
    'label',
    'value',
    'iconBg' => 'bg-violet-100',
    'iconColor' => 'text-thinkra-purple',
])

<div class="thinkra-card p-5 lg:p-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500 font-medium">{{ $label }}</p>
            <p class="text-3xl font-bold text-thinkra-navy mt-2 tracking-tight">{{ $value }}</p>
        </div>
        <div class="thinkra-stat-icon {{ $iconBg }} {{ $iconColor }}">
            @isset($icon){{ $icon }}@endisset
        </div>
    </div>
</div>
