(function () {
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
    const sidebarPanel = document.getElementById('docs-sidebar-panel');
    const backdrop = document.getElementById('docs-drawer-backdrop');

    function setDrawerOpen(open) {
        if (!sidebarPanel) return;
        sidebarPanel.classList.toggle('is-open', open);
        if (backdrop) {
            backdrop.hidden = !open;
            requestAnimationFrame(function () {
                backdrop.classList.toggle('is-visible', open);
            });
        }
        if (drawerToggle) {
            drawerToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        document.body.style.overflow = open ? 'hidden' : '';
    }

    if (drawerToggle && sidebarPanel) {
        drawerToggle.addEventListener('click', function () {
            setDrawerOpen(!sidebarPanel.classList.contains('is-open'));
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function () {
            setDrawerOpen(false);
        });
    }

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

    function applyCompletedUi(completedIds) {
        const set = new Set(completedIds.map(Number));
        document.querySelectorAll('.course-sidebar-lesson').forEach(function (link) {
            const id = Number(link.dataset.lessonId);
            link.classList.toggle('is-completed', set.has(id));
            const icon = link.querySelector('.course-sidebar-lesson-icon');
            if (!icon) return;
            if (set.has(id) && !link.classList.contains('is-active')) {
                icon.innerHTML = '<svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>';
            }
        });
        updateProgressBar(set);
    }

    function updateProgressBar(completedSet) {
        const links = document.querySelectorAll('.course-sidebar-lesson[data-lesson-id]');
        const total = links.length;
        if (!total) return;
        let completed = 0;
        links.forEach(function (l) {
            if (completedSet.has(Number(l.dataset.lessonId))) completed++;
        });
        const percent = Math.round((completed / total) * 100);
        const fill = document.querySelector('.lesson-course-progress-fill');
        const sidebarFill = document.querySelector('.course-sidebar-progress-fill');
        if (fill) fill.style.width = percent + '%';
        if (sidebarFill) sidebarFill.style.width = percent + '%';
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

        if (!config.canTrackProgress && loadGuestCompleted().includes(lessonId)) {
            completeBtn.classList.add('is-done');
            completeBtn.querySelector('span:last-child').textContent = 'تم إكمال الدرس';
        }

        completeBtn.addEventListener('click', async function () {
            if (completeBtn.classList.contains('is-done') && config.canTrackProgress) {
                return;
            }

            if (config.canTrackProgress && config.completeUrl) {
                try {
                    const res = await fetch(config.completeUrl, {
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
                    applyCompletedUi(completedIds);
                    completeBtn.classList.add('is-done');
                    completeBtn.querySelector('span:last-child').textContent = 'تم إكمال الدرس';

                    if (autoAdvance && data.nextUrl) {
                        window.location.href = data.nextUrl;
                    }
                } catch (e) {
                    alert('تعذر حفظ التقدم. حاول مرة أخرى.');
                }
            } else {
                const ids = loadGuestCompleted();
                if (!ids.includes(lessonId)) {
                    ids.push(lessonId);
                }
                saveGuestCompleted(ids);
                applyCompletedUi(ids);
                completeBtn.classList.add('is-done');
                completeBtn.querySelector('span:last-child').textContent = 'تم إكمال الدرس';

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
})();
