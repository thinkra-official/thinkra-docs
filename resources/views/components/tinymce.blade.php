@props(['name' => 'main_content', 'value' => '', 'height' => 480, 'editorId' => 'main_content'])

@php
    $textareaId = 'editor-'.$name;
@endphp

<textarea
    id="{{ $textareaId }}"
    name="{{ $name }}"
    class="hidden"
>{{ old($name, $value) }}</textarea>

<div class="thinkra-tinymce-wrap" data-field="{{ $name }}" data-editor-key="{{ $editorId }}">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
        <p class="text-xs text-slate-400">الصق المحتوى من ChatGPT أو Google Docs — سيتم ضبطه تلقائياً للعرض من اليمين لليسار.</p>
        <button type="button"
                class="thinkra-lesson-preview-toggle text-xs font-semibold text-thinkra-purple hover:underline"
                data-preview-for="{{ $name }}"
                aria-expanded="false">
            معاينة المحتوى
        </button>
    </div>
    <div class="rounded-xl border border-slate-200 overflow-hidden bg-white">
        <textarea id="tinymce-{{ $name }}" class="w-full"></textarea>
    </div>
    <div id="thinkra-preview-{{ $name }}" class="thinkra-editor-preview" aria-hidden="true">
        <p class="thinkra-editor-preview-label">معاينة كما سيظهر للقارئ</p>
        <div class="thinkra-lesson-prose" data-preview-content="{{ $name }}"></div>
    </div>
</div>

@once
    @push('head')
    <link rel="stylesheet" href="{{ asset('css/lesson-content.css') }}">
    @endpush
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tinymce@7.6.1/tinymce.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce-i18n@24.12.30/langs7/ar.js"></script>
    @endpush
@endonce

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fieldName = @json($name);
    const editorKey = @json($editorId);
    const textareaId = @json($textareaId);
    const selector = '#tinymce-' + fieldName;
    const hiddenSource = document.getElementById(textareaId);
    const previewPanel = document.getElementById('thinkra-preview-' + fieldName);
    const previewContent = previewPanel?.querySelector('[data-preview-content]');
    const previewToggle = document.querySelector('[data-preview-for="' + fieldName + '"]');

    if (!hiddenSource || typeof tinymce === 'undefined') {
        return;
    }

    const contentStyle = [
        'body {',
        '  direction: rtl;',
        '  text-align: right;',
        "  font-family: 'Cairo', 'Segoe UI', Tahoma, 'Noto Naskh Arabic', Arial, sans-serif;",
        '  font-size: 16px;',
        '  line-height: 1.85;',
        '  color: #1e293b;',
        '  max-width: 42rem;',
        '  margin: 0 auto;',
        '  padding: 1rem 1.25rem;',
        '}',
        'p { margin: 0 0 1.1em; }',
        'h2 { font-size: 1.5rem; font-weight: 700; margin: 1.5em 0 0.65em; color: #0f206c; }',
        'h3 { font-size: 1.2rem; font-weight: 600; margin: 1.25em 0 0.5em; color: #0f206c; }',
        'ul, ol { margin: 0 0 1.1em; padding-right: 1.65em; padding-left: 0; }',
        'li { margin-bottom: 0.35em; }',
        'blockquote { border-right: 4px solid #9e27b5; border-left: none; padding: 0.5em 1em 0.5em 0; margin: 1em 0; color: #475569; background: rgba(158,39,181,0.06); }',
        'hr { border: none; border-top: 1px solid #e2e8f0; margin: 1.5em 0; }',
        'a { color: #9e27b5; }',
    ].join('\n');

    function syncHidden(editor) {
        hiddenSource.value = editor.getContent();
    }

    function updatePreview(editor) {
        if (previewContent) {
            previewContent.innerHTML = editor.getContent() || '<p class="text-slate-400">لا محتوى بعد</p>';
        }
    }

    function normalizePastedHtml(html) {
        if (!html) return html;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        wrapper.setAttribute('dir', 'rtl');
        wrapper.style.textAlign = 'right';
        wrapper.querySelectorAll('p, h2, h3, li, blockquote, ul, ol').forEach(function (el) {
            if (!el.getAttribute('dir')) {
                el.setAttribute('dir', 'rtl');
            }
            if (!el.style.textAlign) {
                el.style.textAlign = 'right';
            }
        });
        return wrapper.innerHTML;
    }

    tinymce.init({
        selector: selector,
        language: 'ar',
        directionality: 'rtl',
        height: @json($height),
        menubar: false,
        branding: false,
        promotion: false,
        statusbar: true,
        resize: true,
        autoresize_bottom_margin: 24,
        plugins: 'lists link autolink directionality code autoresize wordcount paste',
        toolbar: 'undo redo | blocks | bold italic | bullist numlist | link blockquote hr | removeformat | ltr rtl | code',
        block_formats: 'فقرة=p; عنوان رئيسي=h2; عنوان فرعي=h3',
        default_link_target: '_blank',
        link_default_protocol: 'https',
        paste_as_text: false,
        paste_data_images: false,
        paste_remove_styles_if_webkit: true,
        paste_webkit_styles: 'none',
        content_style: contentStyle,
        setup: function (editor) {
            editor.on('init', function () {
                const initial = hiddenSource.value || '';
                if (initial) {
                    editor.setContent(initial);
                }
                syncHidden(editor);
                updatePreview(editor);

                window.ThinkraEditors = window.ThinkraEditors || {};
                window.ThinkraEditors[editorKey] = {
                    getData: function () { return editor.getContent(); },
                    setData: function (html) {
                        editor.setContent(html || '');
                        syncHidden(editor);
                        updatePreview(editor);
                    },
                };

                document.dispatchEvent(new CustomEvent('thinkra-editor-ready', {
                    detail: { key: editorKey, fieldName: fieldName },
                }));
            });

            editor.on('change keyup undo redo', function () {
                syncHidden(editor);
                updatePreview(editor);
                document.dispatchEvent(new CustomEvent('thinkra-lesson-changed', {
                    detail: { key: editorKey },
                }));
            });

            editor.on('PastePostProcess', function (e) {
                e.node.innerHTML = normalizePastedHtml(e.node.innerHTML);
            });
        },
    }).catch(function (error) {
        console.error('TinyMCE init failed:', error);
        const fallback = document.createElement('textarea');
        fallback.name = fieldName;
        fallback.id = textareaId;
        fallback.className = 'w-full rounded-xl border px-4 py-2.5 text-sm';
        fallback.style.minHeight = '320px';
        fallback.value = hiddenSource.value;
        document.querySelector(selector)?.replaceWith(fallback);
    });

    if (previewToggle && previewPanel) {
        previewToggle.addEventListener('click', function () {
            const visible = previewPanel.classList.toggle('is-visible');
            previewToggle.setAttribute('aria-expanded', visible ? 'true' : 'false');
            previewToggle.textContent = visible ? 'إخفاء المعاينة' : 'معاينة المحتوى';
            if (visible) {
                const ed = tinymce.get('tinymce-' + fieldName);
                if (ed) updatePreview(ed);
            }
        });
    }

    const form = hiddenSource.closest('form');
    if (form) {
        form.addEventListener('submit', function () {
            const ed = tinymce.get('tinymce-' + fieldName);
            if (ed) syncHidden(ed);
        });
    }
});
</script>
@endpush
