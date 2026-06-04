/**
 * Thinkra Docs — lesson editor autosave & draft protection
 */
(function () {
    const config = window.ThinkraLessonEditor;
    if (!config || !config.canEdit) return;

    const form = document.getElementById('lesson-form');
    if (!form) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const saveStatusEl = document.getElementById('save-status');
    const lastSaveEl = document.getElementById('last-save-time');
    const draftBanner = document.getElementById('draft-recovery-banner');

    let baseline = '';
    let dirty = false;
    let saving = false;
    let idleTimer = null;
    let intervalTimer = null;
    let lastSavedAt = config.serverSavedAt || '';

    const storageKey = 'thinkra_lesson_draft_' + config.lessonId;

    function getPayload() {
        const editor = window.ThinkraEditors?.main_content;
        return {
            title: form.querySelector('[name="title"]')?.value ?? '',
            objective: form.querySelector('[name="objective"]')?.value ?? '',
            main_content: editor ? editor.getData() : (form.querySelector('[name="main_content"]')?.value ?? ''),
            teacher_notes: form.querySelector('[name="teacher_notes"]')?.value ?? '',
        };
    }

    function payloadHash(payload) {
        return JSON.stringify(payload);
    }

    function setStatus(state, text) {
        if (!saveStatusEl) return;
        saveStatusEl.dataset.state = state;
        saveStatusEl.textContent = text;
        saveStatusEl.className = 'text-sm font-medium ' + ({
            saving: 'text-amber-600',
            saved: 'text-emerald-600',
            error: 'text-red-600',
            dirty: 'text-slate-500',
        }[state] || 'text-slate-500');
    }

    function updateLastSave(formatted) {
        if (lastSaveEl && formatted) {
            lastSaveEl.textContent = formatted;
        }
    }

    function markDirty() {
        const current = payloadHash(getPayload());
        dirty = current !== baseline;
        if (dirty && !saving) {
            setStatus('dirty', 'تغييرات غير محفوظة');
        }
        persistLocalDraft();
        scheduleIdleSave();
    }

    function persistLocalDraft() {
        try {
            localStorage.setItem(storageKey, JSON.stringify({
                payload: getPayload(),
                savedAt: new Date().toISOString(),
                serverSavedAt: lastSavedAt,
            }));
        } catch (e) {
            /* quota */
        }
    }

    function clearLocalDraft() {
        try {
            localStorage.removeItem(storageKey);
        } catch (e) {
            /* ignore */
        }
    }

    function scheduleIdleSave() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(function () {
            if (dirty) autosave();
        }, config.idleMs || 3000);
    }

    async function autosave() {
        if (saving || !dirty) return;

        const payload = getPayload();
        if (!payload.title.trim()) return;

        saving = true;
        setStatus('saving', 'جاري الحفظ...');

        try {
            const res = await fetch(config.autosaveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();

            if (!res.ok || !data.ok) {
                throw new Error(data.message || 'save failed');
            }

            baseline = payloadHash(payload);
            dirty = false;
            lastSavedAt = data.saved_at;
            updateLastSave(data.saved_at_formatted);
            setStatus('saved', 'تم الحفظ');
            clearLocalDraft();
        } catch (err) {
            console.error(err);
            setStatus('error', 'فشل الحفظ');
        } finally {
            saving = false;
        }
    }

    function applyPayload(payload) {
        form.querySelector('[name="title"]').value = payload.title || '';
        form.querySelector('[name="objective"]').value = payload.objective || '';
        form.querySelector('[name="teacher_notes"]').value = payload.teacher_notes || '';
        const editor = window.ThinkraEditors?.main_content;
        if (editor) {
            editor.setData(payload.main_content || '');
        } else {
            const ta = form.querySelector('[name="main_content"]');
            if (ta) ta.value = payload.main_content || '';
        }
        baseline = payloadHash(getPayload());
        dirty = false;
        setStatus('saved', 'تم الحفظ');
    }

    function checkLocalDraft() {
        try {
            const raw = localStorage.getItem(storageKey);
            if (!raw) return;

            const draft = JSON.parse(raw);
            if (!draft?.payload) return;

            const draftTime = new Date(draft.savedAt).getTime();
            const serverTime = new Date(config.serverSavedAt).getTime();

            if (draftTime <= serverTime) {
                clearLocalDraft();
                return;
            }

            if (draftBanner) {
                draftBanner.classList.remove('hidden');
                document.getElementById('draft-restore-btn')?.addEventListener('click', function () {
                    applyPayload(draft.payload);
                    autosave();
                    draftBanner.classList.add('hidden');
                });
                document.getElementById('draft-discard-btn')?.addEventListener('click', function () {
                    clearLocalDraft();
                    draftBanner.classList.add('hidden');
                });
            }
        } catch (e) {
            console.error(e);
        }
    }

    form.querySelectorAll('input, textarea').forEach(function (el) {
        el.addEventListener('input', markDirty);
    });

    document.addEventListener('thinkra-lesson-changed', markDirty);

    document.addEventListener('thinkra-ckeditor-ready', function () {
        baseline = payloadHash(getPayload());
        dirty = false;
        setStatus('saved', 'تم الحفظ');
        checkLocalDraft();
    });

    form.addEventListener('submit', function () {
        const editor = window.ThinkraEditors?.main_content;
        if (editor) {
            form.querySelector('[name="main_content"]').value = editor.getData();
        }
        clearLocalDraft();
    });

    window.addEventListener('beforeunload', function (e) {
        if (dirty) {
            persistLocalDraft();
            e.preventDefault();
            e.returnValue = '';
        }
    });

    intervalTimer = setInterval(function () {
        if (dirty && !saving) autosave();
    }, config.intervalMs || 10000);

    baseline = payloadHash(getPayload());
    updateLastSave(config.lastSaveFormatted);
    setStatus('saved', 'تم الحفظ');

    if (!window.ThinkraEditors?.main_content) {
        setTimeout(checkLocalDraft, 500);
    }

    /* Versions tab */
    window.restoreLessonVersion = async function (versionId) {
        if (!confirm('استعادة هذه النسخة؟ سيتم حفظ نسخة من المحتوى الحالي أولاً.')) return;

        const url = config.versionRestoreUrlTemplate.replace('__VERSION__', versionId);

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await res.json();
            if (!data.ok) throw new Error(data.message);

            applyPayload(data.lesson);
            updateLastSave(data.saved_at_formatted);
            await autosave();
            alert('تم استعادة النسخة بنجاح');
            window.location.reload();
        } catch (e) {
            alert('فشل استعادة النسخة');
        }
    };

    window.previewLessonVersion = async function (versionId) {
        const url = config.versionShowUrlTemplate.replace('__VERSION__', versionId);
        const panel = document.getElementById('version-preview-panel');
        if (!panel) return;

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            panel.innerHTML = `
                <p class="text-xs text-slate-500 mb-2">${data.created_at_formatted} — ${data.author || ''}</p>
                <h4 class="font-bold text-thinkra-navy mb-2">${escapeHtml(data.title)}</h4>
                <div class="prose prose-sm max-w-none text-slate-700">${data.main_content || '<p class="text-slate-400">لا محتوى</p>'}</div>
            `;
            panel.classList.remove('hidden');
        } catch (e) {
            panel.textContent = 'تعذر تحميل المعاينة';
        }
    };

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }
})();
