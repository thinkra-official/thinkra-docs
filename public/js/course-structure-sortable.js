/**
 * Drag-and-drop reorder for course structure (SortableJS).
 */
(function () {
    const root = document.getElementById('course-structure-reorder');
    if (!root || root.dataset.enabled !== '1') {
        return;
    }

    const reorderUrl = root.dataset.reorderUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const statusEl = document.getElementById('structure-reorder-status');

    let saveTimer = null;
    let saving = false;

    function setStatus(message, type) {
        if (!statusEl) {
            return;
        }
        statusEl.textContent = message;
        statusEl.classList.remove('hidden', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-800', 'border-red-200', 'bg-red-50', 'text-red-800', 'border-slate-200', 'bg-slate-50', 'text-slate-600');
        if (type === 'success') {
            statusEl.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');
        } else if (type === 'error') {
            statusEl.classList.add('border-red-200', 'bg-red-50', 'text-red-800');
        } else {
            statusEl.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
        }
    }

    function collectStructure() {
        const sections = [];
        const sectionsList = document.getElementById('course-sections-sortable');
        if (!sectionsList) {
            return { sections: [] };
        }

        sectionsList.querySelectorAll(':scope > li[data-section-id]').forEach((sectionLi, sectionIndex) => {
            const sectionId = parseInt(sectionLi.dataset.sectionId, 10);
            const sectionRoot = sectionLi.querySelector('[data-section-root]');
            const lessons = [];
            const subSections = [];

            if (sectionRoot) {
                sectionRoot.querySelectorAll('.direct-lessons-sortable > li[data-lesson-id]').forEach((lessonLi, lessonIndex) => {
                    lessons.push({
                        id: parseInt(lessonLi.dataset.lessonId, 10),
                        position: lessonIndex + 1,
                    });
                });

                sectionRoot.querySelectorAll('.subsections-sortable > li[data-sub-section-id]').forEach((subLi, subIndex) => {
                    const subSectionId = parseInt(subLi.dataset.subSectionId, 10);
                    const subLessons = [];
                    subLi.querySelectorAll('.sub-lessons-sortable > li[data-lesson-id]').forEach((lessonLi, lessonIndex) => {
                        subLessons.push({
                            id: parseInt(lessonLi.dataset.lessonId, 10),
                            position: lessonIndex + 1,
                        });
                    });
                    subSections.push({
                        id: subSectionId,
                        position: subIndex + 1,
                        lessons: subLessons,
                    });
                });
            }

            sections.push({
                id: sectionId,
                position: sectionIndex + 1,
                lessons,
                subSections,
            });
        });

        return { sections };
    }

    async function saveStructure() {
        if (saving) {
            return;
        }
        saving = true;
        setStatus('جاري حفظ الترتيب…', 'pending');

        try {
            const response = await fetch(reorderUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(collectStructure()),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message || 'تعذر حفظ الترتيب.');
            }

            setStatus(data.message || 'تم حفظ الترتيب.', 'success');
        } catch (error) {
            setStatus(error.message || 'حدث خطأ أثناء الحفظ.', 'error');
            setTimeout(() => window.location.reload(), 1500);
        } finally {
            saving = false;
        }
    }

    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveStructure, 400);
    }

    const sortableOptions = {
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        fallbackOnBody: true,
        swapThreshold: 0.65,
        onEnd: scheduleSave,
    };

    const sectionsEl = document.getElementById('course-sections-sortable');
    if (sectionsEl && root.dataset.canManage === '1') {
        Sortable.create(sectionsEl, {
            ...sortableOptions,
            handle: '.drag-handle-section',
        });
    }

    document.querySelectorAll('.subsections-sortable').forEach((el) => {
        if (root.dataset.canManage === '1') {
            Sortable.create(el, {
                ...sortableOptions,
                handle: '.drag-handle-subsection',
                group: 'subsections-' + el.dataset.sectionId,
            });
        }
    });

    document.querySelectorAll('.sortable-lessons').forEach((el) => {
        if (root.dataset.canEdit === '1') {
            Sortable.create(el, {
                ...sortableOptions,
                handle: '.drag-handle-lesson',
                group: {
                    name: 'course-lessons',
                    pull: true,
                    put: true,
                },
                emptyInsertThreshold: 8,
            });
        }
    });
})();
