<dialog id="docs-course-menu" class="docs-course-menu" aria-labelledby="docs-course-menu-title">
    <div class="docs-course-menu-sheet">
        <header class="docs-course-menu-toolbar">
            <h2 id="docs-course-menu-title" class="docs-course-menu-title">محتوى الدورة</h2>
            <button type="button" class="docs-course-menu-close" data-docs-menu-close aria-label="إغلاق القائمة">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </header>
        <div class="docs-course-menu-body">
            @include('docs.partials.course-sidebar', [
                'course' => $course,
                'sections' => $sections,
                'currentLesson' => $currentLesson ?? null,
                'preview' => $preview ?? false,
                'progressStats' => $progressStats ?? null,
                'completedLessonIds' => $completedLessonIds ?? [],
                'expandedSections' => $expandedSections ?? [],
                'expandedSubSections' => $expandedSubSections ?? [],
            ])
        </div>
    </div>
</dialog>

<script>
(function () {
    var menu = document.getElementById('docs-course-menu');
    var openBtn = document.getElementById('docs-burger-open');
    if (!menu || !openBtn) return;

    function setExpanded(open) {
        openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function openMenu() {
        document.body.classList.add('docs-menu-open');
        setExpanded(true);
        if (typeof menu.showModal === 'function') {
            try {
                menu.showModal();
                return;
            } catch (err) {
                /* fallback below */
            }
        }
        menu.setAttribute('open', '');
        menu.classList.add('is-open');
    }

    function closeMenu() {
        document.body.classList.remove('docs-menu-open');
        setExpanded(false);
        if (menu.open && typeof menu.close === 'function') {
            menu.close();
        }
        menu.removeAttribute('open');
        menu.classList.remove('is-open');
    }

    openBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (menu.open || menu.hasAttribute('open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    document.querySelectorAll('[data-docs-menu-close]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            closeMenu();
        });
    });

    menu.addEventListener('click', function (e) {
        if (e.target === menu) {
            closeMenu();
        }
    });

    menu.addEventListener('cancel', function (e) {
        e.preventDefault();
        closeMenu();
    });

    menu.querySelectorAll('.course-sidebar a').forEach(function (link) {
        link.addEventListener('click', function () {
            closeMenu();
        });
    });

    window.addEventListener('resize', function () {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            closeMenu();
        }
    });

    window.docsCloseCourseMenu = closeMenu;
})();
</script>
