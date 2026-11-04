/**
 * Hydynamic – front-end interactie (vanilla JS, geen afhankelijkheden)
 * - Hoofdmenu: uitklappaneel (mobiel) + toegankelijke dropdowns (desktop)
 * - Zoeken: paneel met live suggesties via /wp-json/hyd/v1/search
 * - Offerteknoppen: scrollen naar #offerte op dezelfde pagina
 * - Sliders: scroll-snap met knoppen en teller
 * - Taalkeuze (WPML)
 */
(function () {
    'use strict';

    var cfg = window.hydConfig || { i18n: {} };
    var lgQuery = window.matchMedia('(min-width: 1320px)');

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    /* ------------------------------------------------------------------ menu */
    function initMenu() {
        var nav = document.getElementById('mk-nav');
        var btn = document.querySelector('.js-menu-toggle');
        var backdrop = document.querySelector('.mk-nav-backdrop');
        if (!nav || !btn) return;

        function setOpen(open) {
            nav.classList.toggle('is-open', open);
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.setAttribute('aria-label', open ? (cfg.i18n.menuClose || 'Menu sluiten') : (cfg.i18n.menuOpen || 'Menu openen'));
            document.body.classList.toggle('freeze', open);
            if (backdrop) backdrop.hidden = !open;
            if (open) {
                var first = nav.querySelector('a, button');
                if (first) first.focus();
            }
        }

        btn.addEventListener('click', function () {
            setOpen(!nav.classList.contains('is-open'));
        });
        if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });

        // Submenu-toggles (beide weergaven)
        nav.querySelectorAll('.mk-nav__toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                var item = toggle.closest('.mk-nav__item');
                var open = !item.classList.contains('is-open');
                // Siblings sluiten
                Array.prototype.forEach.call(item.parentNode.children, function (sib) {
                    if (sib !== item) closeItem(sib);
                });
                item.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (!open) item.querySelectorAll('.is-open').forEach(closeItem);
            });
        });

        function closeItem(item) {
            item.classList.remove('is-open');
            var t = item.querySelector(':scope > .mk-nav__row > .mk-nav__toggle');
            if (t) t.setAttribute('aria-expanded', 'false');
        }

        // Desktop: hover synchroniseert aria-expanded
        nav.querySelectorAll('.mk-nav__item.has-children').forEach(function (item) {
            var t = item.querySelector(':scope > .mk-nav__row > .mk-nav__toggle');
            item.addEventListener('mouseenter', function () { if (lgQuery.matches && t) t.setAttribute('aria-expanded', 'true'); });
            item.addEventListener('mouseleave', function () {
                if (lgQuery.matches) closeItem(item);
            });
        });

        // Escape sluit alles
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var openItem = nav.querySelector('.mk-nav__item.is-open');
            if (openItem) {
                var t = openItem.querySelector(':scope > .mk-nav__row > .mk-nav__toggle');
                nav.querySelectorAll('.is-open').forEach(closeItem);
                if (t) t.focus();
            } else if (nav.classList.contains('is-open')) {
                setOpen(false);
                btn.focus();
            }
        });

        // Klik buiten het menu sluit dropdowns (desktop)
        document.addEventListener('click', function (e) {
            if (!nav.contains(e.target)) nav.querySelectorAll('.mk-nav__item.is-open').forEach(closeItem);
        });

        // Bij wisselen naar desktop: paneel resetten
        lgQuery.addEventListener('change', function () { setOpen(false); });

        // Klik op een ankerlink in het mobiele menu sluit het paneel
        nav.addEventListener('click', function (e) {
            var a = e.target.closest('a');
            if (a && a.getAttribute('href') && a.getAttribute('href').charAt(0) === '#') setOpen(false);
        });
    }

    /* --------------------------------------------------------- header scroll */
    function initHeaderScroll() {
        var header = document.querySelector('.mk-header--overlay');
        if (!header) return;
        var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 40); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------------------------------------------------------------- zoeken */
    function initSearch() {
        var panel = document.getElementById('mk-search');
        var openBtn = document.querySelector('.js-search-open');
        if (!panel || !openBtn) return;
        var input = panel.querySelector('.js-search-input');
        var results = panel.querySelector('.mk-search__results');
        var status = panel.querySelector('.mk-search__status');
        var closeBtn = panel.querySelector('.js-search-close');
        var timer, controller, active = -1;

        function open() {
            panel.hidden = false;
            openBtn.setAttribute('aria-expanded', 'true');
            setTimeout(function () { input.focus(); input.select(); }, 30);
        }
        function close(returnFocus) {
            panel.hidden = true;
            openBtn.setAttribute('aria-expanded', 'false');
            input.setAttribute('aria-expanded', 'false');
            if (returnFocus) openBtn.focus();
        }

        openBtn.addEventListener('click', function () { panel.hidden ? open() : close(true); });
        closeBtn.addEventListener('click', function () { close(true); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) close(true);
        });
        document.addEventListener('click', function (e) {
            if (!panel.hidden && !panel.contains(e.target) && !openBtn.contains(e.target)) close(false);
        });

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }
        function highlight(text, term) {
            var safe = escapeHtml(text);
            var t = term.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            return t ? safe.replace(new RegExp('(' + t + ')', 'ig'), '<mark>$1</mark>') : safe;
        }

        function render(items, term) {
            active = -1;
            var allUrl = cfg.homeUrl + '?s=' + encodeURIComponent(term);
            if (!items.length) {
                results.innerHTML = '<p class="mk-search__empty">' + escapeHtml(cfg.i18n.noResults || 'Geen resultaten.') + '</p>';
            } else {
                results.innerHTML = items.map(function (it, i) {
                    return '<a class="mk-search__result" role="option" id="mk-sr-' + i + '" href="' + escapeHtml(it.url) + '">' +
                        '<span class="mk-search__result-title">' + highlight(it.title, term) + '</span>' +
                        '<span class="mk-search__result-type">' + escapeHtml(it.type) + '</span>' +
                        (it.text ? '<span class="mk-search__result-text">' + escapeHtml(it.text) + '</span>' : '') +
                        '</a>';
                }).join('') + '<a class="mk-search__all" href="' + escapeHtml(allUrl) + '">' + escapeHtml(cfg.i18n.allResults || 'Bekijk alle resultaten') + ' →</a>';
            }
            input.setAttribute('aria-expanded', 'true');
            status.textContent = (cfg.i18n.results || '%d suggesties').replace('%d', items.length);
        }

        input.addEventListener('input', function () {
            var term = input.value.trim();
            clearTimeout(timer);
            if (term.length < 2) {
                results.innerHTML = '';
                input.setAttribute('aria-expanded', 'false');
                return;
            }
            timer = setTimeout(function () {
                if (controller) controller.abort();
                controller = 'AbortController' in window ? new AbortController() : null;
                fetch(cfg.searchUrl + '?q=' + encodeURIComponent(term), controller ? { signal: controller.signal } : {})
                    .then(function (r) { return r.ok ? r.json() : []; })
                    .then(function (data) { if (input.value.trim() === term) render(Array.isArray(data) ? data : [], term); })
                    .catch(function () { /* afgebroken of offline: formulier werkt nog steeds */ });
            }, 220);
        });

        // Pijltjestoetsen door de suggesties
        input.addEventListener('keydown', function (e) {
            var opts = results.querySelectorAll('.mk-search__result');
            if (!opts.length || (e.key !== 'ArrowDown' && e.key !== 'ArrowUp')) return;
            e.preventDefault();
            active = e.key === 'ArrowDown' ? Math.min(active + 1, opts.length - 1) : Math.max(active - 1, 0);
            opts.forEach(function (o, i) { o.classList.toggle('is-active', i === active); });
            input.setAttribute('aria-activedescendant', opts[active].id);
            opts[active].scrollIntoView({ block: 'nearest' });
        });
        input.form.addEventListener('submit', function (e) {
            var opts = results.querySelectorAll('.mk-search__result');
            if (active > -1 && opts[active]) {
                e.preventDefault();
                window.location.href = opts[active].href;
            }
        });
    }

    /* ------------------------------------------------------------ offerte */
    function initOfferte() {
        document.addEventListener('click', function (e) {
            var link = e.target.closest('a.js-offerte, a[href="#offerte"]');
            if (!link) return;
            var target = document.getElementById('offerte');
            if (!target) {
                var fallback = link.getAttribute('data-fallback');
                if (fallback) {
                    e.preventDefault();
                    window.location.href = fallback;
                }
                return;
            }
            e.preventDefault();
            target.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
            if (history.replaceState) history.replaceState(null, '', '#offerte');
            // Focus op het eerste formulierveld zonder opnieuw te scrollen
            var field = target.querySelector('input:not([type="hidden"]), textarea, select');
            setTimeout(function () { (field || target).focus({ preventScroll: true }); }, 450);
        });
    }

    /* ------------------------------------------------------------ sliders */
    function initSliders() {
        document.querySelectorAll('.js-slider').forEach(function (slider) {
            var track = slider.querySelector('.js-slider-track');
            var slides = track ? track.children : [];
            var prev = slider.querySelector('.js-slider-prev');
            var next = slider.querySelector('.js-slider-next');
            var counter = slider.querySelector('.js-slider-counter');
            if (!track || slides.length < 2) return;
            var loop = !slider.classList.contains('mk-slider--gallery');

            function step() {
                var s = slides[0];
                var gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap) || 0;
                return s.getBoundingClientRect().width + gap;
            }
            function index() { return Math.round(track.scrollLeft / step()); }
            function maxScroll() { return track.scrollWidth - track.clientWidth - 2; }

            function update() {
                if (counter) counter.textContent = (Math.min(index(), slides.length - 1) + 1) + ' / ' + slides.length;
                if (!loop) {
                    if (prev) prev.disabled = track.scrollLeft <= 2;
                    if (next) next.disabled = track.scrollLeft >= maxScroll();
                }
            }
            function go(dir) {
                var atEnd = track.scrollLeft >= maxScroll();
                var atStart = track.scrollLeft <= 2;
                if (loop && dir > 0 && atEnd) track.scrollTo({ left: 0 });
                else if (loop && dir < 0 && atStart) track.scrollTo({ left: track.scrollWidth });
                else track.scrollBy({ left: dir * step() });
            }

            if (prev) prev.addEventListener('click', function () { go(-1); });
            if (next) next.addEventListener('click', function () { go(1); });
            track.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowRight') { e.preventDefault(); go(1); }
                if (e.key === 'ArrowLeft') { e.preventDefault(); go(-1); }
            });
            var raf;
            track.addEventListener('scroll', function () {
                cancelAnimationFrame(raf);
                raf = requestAnimationFrame(update);
            }, { passive: true });
            update();
        });
    }

    /* ------------------------------------------------------------ taal */
    function initLang() {
        var btn = document.querySelector('.js-lang-toggle');
        var list = document.getElementById('mk-lang-list');
        if (!btn || !list) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = list.hidden;
            list.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function () {
            list.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    /* ---------------------------------------------------------- FAQ */
    // Eén vraag tegelijk open (zoals een accordeon)
    function initFaq() {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var DURATION = 320;

        function animate(item, open) {
            var answer = item.querySelector('.mk-faq__answer');
            if (!answer || reduce || !answer.animate) {
                item.open = open;
                return;
            }
            if (item._anim) item._anim.cancel();
            clearTimeout(item._done);
            item.classList.toggle('is-opening', open);
            item.classList.toggle('is-closing', !open);
            if (open) item.open = true;
            var full = answer.scrollHeight;
            var from = open ? 0 : answer.offsetHeight;
            var to = open ? full : 0;
            answer.style.overflow = 'hidden';
            item._anim = answer.animate([
                { height: from + 'px', opacity: open ? 0 : 1 },
                { height: to + 'px', opacity: open ? 1 : 0 }
            ], { duration: DURATION, easing: 'cubic-bezier(.4, 0, .2, 1)' });
            var finish = function () {
                clearTimeout(item._done);
                if (item._anim) { item._anim.cancel(); item._anim = null; }
                answer.style.overflow = '';
                item.classList.remove('is-opening', 'is-closing');
                if (!open) item.open = false;
            };
            item._anim.onfinish = finish;
            item._done = setTimeout(finish, DURATION + 60); // vangnet als 'finish' niet afgaat
        }

        document.querySelectorAll('.mk-faq__list').forEach(function (list) {
            list.querySelectorAll('.mk-faq__item').forEach(function (item) {
                var summary = item.querySelector('summary');
                summary.addEventListener('click', function (e) {
                    e.preventDefault();
                    var opening = !item.open || item.classList.contains('is-closing');
                    // Eén vraag tegelijk open (accordeon)
                    if (opening) {
                        list.querySelectorAll('.mk-faq__item[open]').forEach(function (other) {
                            if (other !== item && !other.classList.contains('is-closing')) animate(other, false);
                        });
                    }
                    animate(item, opening);
                });
            });
        });
    }

    /* ---------------------------------------------------------- USP-balk */
    // Op mobiel/tablet een swipebare rij die automatisch doorschuift.
    // Stopt bij aanraken/hover/focus en bij "verminderde beweging".
    function initUsp() {
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        document.querySelectorAll('.mk-usp__list').forEach(function (list) {
            if (list.children.length < 2) return;
            var paused = false, timer;
            ['pointerdown', 'mouseenter', 'focusin'].forEach(function (ev) {
                list.addEventListener(ev, function () { paused = true; });
            });
            list.addEventListener('mouseleave', function () { paused = false; });
            function tick() {
                if (paused || reduce || lgQuery.matches || list.scrollWidth <= list.clientWidth + 2) return;
                var item = list.children[0].getBoundingClientRect().width + (parseFloat(getComputedStyle(list).columnGap) || 0);
                var atEnd = list.scrollLeft + list.clientWidth >= list.scrollWidth - 4;
                list.scrollTo({ left: atEnd ? 0 : list.scrollLeft + item });
            }
            timer = setInterval(tick, 3500);
        });
    }

    /* ---------------------------------------------------- terug naar boven */
    function initToTop() {
        var btn = document.querySelector('.js-to-top');
        if (!btn) return;
        var onScroll = function () { btn.classList.toggle('is-visible', window.scrollY > 600); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        btn.addEventListener('click', function () {
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
            var target = document.getElementById('main-content');
            if (target) target.focus({ preventScroll: true });
        });
    }

    /* -------------------------------------------- zwevende offerteknop */
    // Zichtbaar na 300px scrollen; verdwijnt als het formulier in beeld is of het menu openstaat.
    function initFloatOfferte() {
        var btn = document.querySelector('.js-float-offerte');
        if (!btn) return;
        var form = document.getElementById('offerte');
        var formVisible = false;
        if (form && 'IntersectionObserver' in window) {
            new IntersectionObserver(function (entries) {
                formVisible = entries[0].isIntersecting;
                update();
            }, { threshold: 0.15 }).observe(form);
        }
        function update() {
            var menuOpen = document.body.classList.contains('freeze');
            btn.classList.toggle('is-visible', window.scrollY > 300 && !formVisible && !menuOpen);
        }
        update();
        window.addEventListener('scroll', update, { passive: true });
        document.addEventListener('click', function () { setTimeout(update, 0); });
    }

    ready(function () {
        initUsp();
        initFloatOfferte();
        initToTop();
        initMenu();
        initHeaderScroll();
        initSearch();
        initOfferte();
        initSliders();
        initLang();
        initFaq();
    });
})();
