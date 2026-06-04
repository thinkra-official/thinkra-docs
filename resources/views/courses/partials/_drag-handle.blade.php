@props(['type' => 'lesson', 'title' => 'سحب'])

<span
    @class([
        'drag-handle shrink-0 inline-flex items-center justify-center rounded border border-slate-200 bg-white text-slate-400 hover:text-thinkra-purple hover:border-thinkra-purple/40 cursor-grab active:cursor-grabbing',
        'drag-handle-section w-8 h-8' => $type === 'section',
        'drag-handle-subsection w-7 h-7' => $type === 'subsection',
        'drag-handle-lesson w-7 h-7' => $type === 'lesson',
    ])
    title="{{ $title }}"
    aria-label="{{ $title }}"
>
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>
        <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
        <circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
    </svg>
</span>
