(function () {
    function initDocsReader() {
    document.documentElement.classList.remove('docs-dark');

    const config = window.ThinkraDocsViewer || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    /* Scroll progress */
    const scrollBar = document.getElementById('docs-scroll-progress-bar');
    if (scrollBar) {
        const updateScroll = function () {
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const pct = docHeight > 0 ? Math.min(100, (window.scrollY / docHeight) * 100) : 0;
            scrollBar.style.width = pct + '%';
        };
        window.addEventListener('scroll', updateScroll, { passive: true });
        updateScroll();
    }

    /* Mobile drawer */
    const drawerToggle = document.getElementById('docs-drawer-toggle');
    const drawerClose = document.getElementById('docs-drawer-close');
    const sidebarPanel = document.getElementById('docs-sidebar-panel');
    const backdrop = document.getElementById('docs-drawer-backdrop');

    function isMobileDrawer() {
        return window.matchMedia('(max-width: 1023px)').matches;
    }

    function setDrawerOpen(open) {
        if (!sidebarPanel || !isMobileDrawer()) return;
        sidebarPanel.classList.toggle('is-open', open);
        if (backdrop) {
            if (open) {
                backdrop.removeAttribute('hidden');
                requestAnimationFrame(function () {
                    backdrop.classList.add('is-visible');
                });
            } else {
                backdrop.classList.remove('is-visible');
                backdrop.setAttribute('hidden', '');
            }
        }
        if (drawerToggle) {
            drawerToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        document.body.classList.toggle('docs-drawer-open', open);
        document.body.style.overflow = open ? 'hidden' : '';
    }

    function toggleDrawer(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (!sidebarPanel) return;
        setDrawerOpen(!sidebarPanel.classList.contains('is-open'));
    }

    if (drawerToggle && sidebarPanel) {
        drawerToggle.addEventListener('click', toggleDrawer);
    }

    if (drawerClose) {
        drawerClose.addEventListener('click', function (e) {
            e.preventDefault();
            setDrawerOpen(false);
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            setDrawerOpen(false);
        });
    }

    document.querySelectorAll('.course-sidebar a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (isMobileDrawer()) {
                setDrawerOpen(false);
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebarPanel?.classList.contains('is-open')) {
            setDrawerOpen(false);
        }
    });

    window.addEventListener('resize', function () {
        if (!isMobileDrawer()) {
            setDrawerOpen(false);
        }
    });

    /* Scroll active lesson into view in sidebar */
    const activeLesson = document.querySelector('.course-sidebar-lesson.is-active');
    if (activeLesson) {
        setTimeout(function () {
            activeLesson.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }, 300);
    }

    /* TOC scroll spy */
    const tocLinks = document.querySelectorAll('[data-toc-link]');
    if (tocLinks.length) {
        const headings = [];
        tocLinks.forEach(function (link) {
            const id = link.getAttribute('href')?.replace('#', '');
            if (id) {
                const el = document.getElementById(id);
                if (el) headings.push({ el: el, link: link });
            }
        });

        if (headings.length && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver(
                function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            const id = entry.target.id;
                            tocLinks.forEach(function (l) {
                                l.classList.toggle('is-active', l.getAttribute('href') === '#' + id);
                            });
                        }
                    });
                },
                { rootMargin: '-20% 0px -70% 0px', threshold: 0 }
            );
            headings.forEach(function (h) {
                observer.observe(h.el);
            });
        }

        tocLinks.forEach(function (link) {
            link.addEventListener('click', function (e) {
                const id = link.getAttribute('href')?.replace('#', '');
                const target = id ? document.getElementById(id) : null;
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    history.replaceState(null, '', '#' + id);
                }
            });
        });
    }

    /* Progress: localStorage for guests */
    function loadGuestCompleted() {
        if (!config.storageKey) return [];
        try {
            const raw = localStorage.getItem(config.storageKey);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            return [];
        }
    }

    function saveGuestCompleted(ids) {
        if (!config.storageKey) return;
        try {
            localStorage.setItem(config.storageKey, JSON.stringify(ids));
        } catch (e) {
            /* quota */
        }
    }

    const LESSON_CHECK_ICON =
        '<svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>';

    function updateSidebarLessonIcon(link, isCompleted) {
        const icon = link.querySelector('.course-sidebar-lesson-icon');
        if (!icon) return;
        const isActive = link.classList.contains('is-active');
        if (isCompleted) {
            icon.innerHTML = LESSON_CHECK_ICON;
        } else if (isActive) {
            icon.innerHTML = '<span class="course-sidebar-lesson-dot is-current"></span>';
        } else {
            icon.innerHTML = '<span class="course-sidebar-lesson-dot"></span>';
        }
    }

    function updateProgressLabels(stats) {
        if (!stats || !stats.total) return;
        const meta = document.querySelector('.lesson-meta-progress');
        if (meta) {
            meta.textContent =
                stats.completed + ' من ' + stats.total + ' دروس · ' + stats.percent + '%';
        }
        const percentEl = document.querySelector('.lesson-course-progress-label .font-semibold');
        if (percentEl) percentEl.textContent = stats.percent + '%';
        const hint = document.querySelector('.lesson-course-progress-hint');
        if (hint) hint.textContent = stats.remaining + ' دروس متبقية';
        const sidebarMeta = document.querySelectorAll('.course-sidebar-progress-meta span');
        if (sidebarMeta.length >= 2) {
            sidebarMeta[0].textContent = stats.percent + '% مكتمل';
            sidebarMeta[1].textContent = stats.completed + '/' + stats.total;
        }
        const bar = document.getElementById('lesson-course-progress-bar');
        if (bar) bar.setAttribute('aria-valuenow', String(stats.percent));
        const sidebarBar = document.querySelector('.course-sidebar-progress-track');
        if (sidebarBar) sidebarBar.setAttribute('aria-valuenow', String(stats.percent));
    }

    function statsFromCompletedSet(completedSet) {
        const links = document.querySelectorAll('.course-sidebar-lesson[data-lesson-id]');
        const total = links.length;
        let completed = 0;
        links.forEach(function (l) {
            if (completedSet.has(Number(l.dataset.lessonId))) completed++;
        });
        const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

        return {
            total: total,
            completed: completed,
            remaining: Math.max(0, total - completed),
            percent: percent,
        };
    }

    function applyCompletedUi(completedIds, progressStats) {
        const set = new Set(completedIds.map(Number));
        document.querySelectorAll('.course-sidebar-lesson').forEach(function (link) {
            const id = Number(link.dataset.lessonId);
            const done = set.has(id);
            link.classList.toggle('is-completed', done);
            updateSidebarLessonIcon(link, done);
        });
        updateProgressBar(set);
        updateProgressLabels(progressStats || statsFromCompletedSet(set));
    }

    function updateProgressBar(completedSet) {
        const stats = statsFromCompletedSet(completedSet);
        const fill = document.querySelector('.lesson-course-progress-fill');
        const sidebarFill = document.querySelector('.course-sidebar-progress-fill');
        if (fill) fill.style.width = stats.percent + '%';
        if (sidebarFill) sidebarFill.style.width = stats.percent + '%';
    }

    function setCompleteButtonState(btn, done) {
        btn.classList.toggle('is-done', done);
        btn.setAttribute('aria-pressed', done ? 'true' : 'false');
        btn.querySelector('span:last-child').textContent = done
            ? 'إلغاء إكمال الدرس'
            : 'تحديد الدرس كمكتمل';
    }

    let completedIds = config.completedIds ? config.completedIds.slice() : [];
    if (!config.canTrackProgress) {
        const guest = loadGuestCompleted();
        if (guest.length) {
            completedIds = guest;
            applyCompletedUi(completedIds);
        }
    }

    const completeBtn = document.getElementById('lesson-complete-btn');
    if (completeBtn) {
        const lessonId = Number(completeBtn.dataset.lessonId);
        const autoAdvance = completeBtn.dataset.autoAdvance === '1';

        if (completeBtn.classList.contains('is-done')) {
            setCompleteButtonState(completeBtn, true);
        } else if (!config.canTrackProgress && loadGuestCompleted().includes(lessonId)) {
            setCompleteButtonState(completeBtn, true);
        }

        completeBtn.addEventListener('click', async function () {
            const isDone = completeBtn.classList.contains('is-done');

            if (config.canTrackProgress) {
                const url = isDone ? config.uncompleteUrl : config.completeUrl;
                if (!url) return;

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
                    if (!data.ok) throw new Error();

                    completedIds = data.completedIds || [];
                    applyCompletedUi(completedIds, data.progress);
                    setCompleteButtonState(completeBtn, !isDone);

                    if (!isDone && autoAdvance && data.nextUrl) {
                        window.location.href = data.nextUrl;
                    }
                } catch (e) {
                    alert('تعذر حفظ التقدم. حاول مرة أخرى.');
                }
                return;
            }

            let ids = loadGuestCompleted();
            if (isDone) {
                ids = ids.filter(function (id) {
                    return Number(id) !== lessonId;
                });
            } else if (!ids.includes(lessonId)) {
                ids.push(lessonId);
            }
            saveGuestCompleted(ids);
            completedIds = ids;
            applyCompletedUi(ids);
            setCompleteButtonState(completeBtn, !isDone);

            if (!isDone) {
                const nextLink = document.querySelector('.lesson-nav-next');
                if (autoAdvance && nextLink?.href) {
                    window.location.href = nextLink.href;
                }
            }
        });
    }

    /* Keyboard navigation */
    document.addEventListener('keydown', function (e) {
        if (e.target.matches('input, textarea, [contenteditable]')) return;
        const prev = document.querySelector('[data-nav="prev"]');
        const next = document.querySelector('[data-nav="next"]');
        if (e.key === 'ArrowLeft' && next) {
            window.location.href = next.href;
        }
        if (e.key === 'ArrowRight' && prev) {
            window.location.href = prev.href;
        }
    });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocsReader);
    } else {
        initDocsReader();
    }
})();
