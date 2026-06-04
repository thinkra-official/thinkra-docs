@php
    $menuProgress = $progressStats ?? ['total' => 0, 'completed' => 0, 'percent' => 0, 'remaining' => 0];
@endphp

<dialog id="docs-course-menu" class="docs-course-menu" aria-labelledby="docs-course-menu-title">
    <div class="docs-course-menu-sheet">
        <div class="docs-course-menu-grab" aria-hidden="true"></div>

        <header class="docs-course-menu-head">
            <div class="docs-course-menu-head-main">
                <p class="docs-course-menu-eyebrow">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    محتوى الدورة
                </p>
                <h2 id="docs-course-menu-title" class="docs-course-menu-course-title">{{ $course->title }}</h2>
                @if(($menuProgress['total'] ?? 0) > 0)
                <div class="docs-course-menu-progress">
                    <div class="docs-course-menu-progress-track" role="progressbar"
                         aria-valuenow="{{ $menuProgress['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="docs-course-menu-progress-fill" style="width: {{ $menuProgress['percent'] }}%"></div>
                    </div>
                    <span class="docs-course-menu-progress-label">
                        {{ $menuProgress['completed'] }}/{{ $menuProgress['total'] }} دروس · {{ $menuProgress['percent'] }}%
                    </span>
                </div>
                @endif
            </div>
            <button type="button" class="docs-course-menu-close" data-docs-menu-close aria-label="إغلاق القائمة">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
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
                'hideHeader' => true,
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
        openBtn.classList.toggle('is-active', open);
    }

    function openMenu() {
        document.body.classList.add('docs-menu-open');
        setExpanded(true);
        if (typeof menu.showModal === 'function') {
            try {
                menu.showModal();
                return;
            } catch (err) {
                /* fallback */
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
