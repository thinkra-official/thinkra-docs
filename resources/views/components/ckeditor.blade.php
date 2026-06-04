@props(['name' => 'main_content', 'value' => '', 'height' => 420, 'editorId' => 'main_content'])

<textarea
    id="editor-{{ $name }}"
    name="{{ $name }}"
    class="hidden"
>{{ old($name, $value) }}</textarea>

<div id="ckeditor-mount-{{ $name }}" class="rounded-xl border border-slate-200 overflow-hidden bg-white" style="min-height: {{ $height }}px"></div>

@once
    @push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    @endpush
@endonce

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fieldName = @json($name);
    const editorKey = @json($editorId);
    const mount = document.getElementById('ckeditor-mount-' + fieldName);
    const source = document.getElementById('editor-' + fieldName);

    if (!mount || !source || typeof ClassicEditor === 'undefined') {
        return;
    }

    ClassicEditor.create(mount, {
        language: 'ar',
        toolbar: [
            'heading', '|',
            'bold', 'italic', 'underline', '|',
            'bulletedList', 'numberedList', '|',
            'insertTable', '|',
            'undo', 'redo'
        ],
        heading: {
            options: [
                { model: 'paragraph', title: 'فقرة', class: 'ck-heading_paragraph' },
                { model: 'heading2', view: 'h2', title: 'عنوان', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'عنوان فرعي', class: 'ck-heading_heading3' },
            ]
        },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
        },
        initialData: source.value,
    }).then(function (editor) {
        editor.setData(source.value);

        window.ThinkraEditors = window.ThinkraEditors || {};
        window.ThinkraEditors[editorKey] = editor;

        document.dispatchEvent(new CustomEvent('thinkra-ckeditor-ready', {
            detail: { key: editorKey, editor: editor, fieldName: fieldName }
        }));

        const form = source.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                source.value = editor.getData();
            });
        }

        editor.model.document.on('change:data', function () {
            document.dispatchEvent(new CustomEvent('thinkra-lesson-changed', {
                detail: { key: editorKey }
            }));
        });
    }).catch(function (error) {
        console.error('CKEditor init failed:', error);
        const fallback = document.createElement('textarea');
        fallback.name = fieldName;
        fallback.className = 'w-full rounded-xl border px-4 py-2.5 text-sm';
        fallback.style.minHeight = '320px';
        fallback.value = source.value;
        mount.replaceWith(fallback);
        fallback.addEventListener('input', function () {
            document.dispatchEvent(new CustomEvent('thinkra-lesson-changed', { detail: { key: editorKey } }));
        });
    });
});
</script>
@endpush
