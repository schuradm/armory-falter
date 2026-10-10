// Livewire 3 bringt Alpine.js mit; hier nur kleine, globale Helfer. Keine Bibliotheken → minimale JS-Last.
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const toast = (message) => window.dispatchEvent(new CustomEvent('toast', { detail: { message } }));
const store = {
    get: (k, d) => { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch (e) { return d; } },
    set: (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
};

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.store('theme', {
        current: document.documentElement.dataset.theme || 'dark',
        toggle() {
            this.current = this.current === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = this.current;
            store.set('af-theme', this.current);
        },
    });

    // Produktvergleich (max. 4 Artikel, lokal gespeichert)
    Alpine.store('compare', {
        items: store.get('af-compare', []),
        has(id) { return this.items.some((i) => i.id === id); },
        toggle(item) {
            if (this.has(item.id)) {
                this.items = this.items.filter((i) => i.id !== item.id);
            } else if (this.items.length < 4) {
                this.items.push(item);
            } else {
                return toast('Maximal 4 Artikel vergleichen.');
            }
            store.set('af-compare', this.items);
        },
        clear() { this.items = []; store.set('af-compare', []); },
        get url() { return '/vergleich?ids=' + this.items.map((i) => i.id).join(','); },
    });

    // Merkliste: IDs einmal pro Seite laden (nur angemeldet), Umschalten per JSON
    const authed = document.documentElement.dataset.auth === '1';
    Alpine.store('wishlist', {
        ids: [],
        init() {
            if (!authed) return;
            fetch('/merkliste/ids', { headers: { Accept: 'application/json' } })
                .then((r) => (r.ok ? r.json() : { ids: [] })).then((d) => { this.ids = d.ids; }).catch(() => {});
        },
        has(id) { return this.ids.includes(id); },
        async toggle(id) {
            if (!authed) { window.location.href = '/konto/anmelden'; return; }
            const r = await fetch(`/merkliste/${id}`, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
            if (!r.ok) return toast('Das hat nicht geklappt. Bitte erneut versuchen.');
            const d = await r.json();
            this.ids = d.active ? [...this.ids, id] : this.ids.filter((x) => x !== id);
            toast(d.message);
        },
    });

    // Schnellkauf aus Produktkarten → öffnet anschließend den Warenkorb-Drawer
    Alpine.store('cart', {
        async add(productId, quantity = 1) {
            const r = await fetch('/warenkorb/artikel', {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify({ product_id: productId, quantity }),
            });
            const d = await r.json().catch(() => ({}));
            if (r.status === 409 && d.redirect) { window.location.href = d.redirect; return; }
            if (!r.ok) { toast(d.message || 'Der Artikel konnte nicht hinzugefügt werden.'); return; }
            window.Livewire.dispatch('cart-updated', { added: d.product_id });
        },
    });
});

// Scroll-Reveal ohne Bibliothek
const io = 'IntersectionObserver' in window && !reduced
    ? new IntersectionObserver((entries) => entries.forEach((e) => {
        if (e.isIntersecting) { e.target.classList.add('is-visible'); io.unobserve(e.target); }
    }), { rootMargin: '0px 0px -8% 0px' })
    : null;
const observeReveals = () => document.querySelectorAll('.reveal:not(.is-visible)').forEach((el) => (io ? io.observe(el) : el.classList.add('is-visible')));

// Zähler, die beim Einblenden hochlaufen: <span data-count="1500" data-suffix="+">
const counters = () => document.querySelectorAll('[data-count]:not([data-counted])').forEach((el) => {
    el.dataset.counted = '1';
    const target = Number(el.dataset.count);
    const suffix = el.dataset.suffix || '';
    if (reduced || !('IntersectionObserver' in window)) { el.textContent = target.toLocaleString('de-DE') + suffix; return; }
    new IntersectionObserver(([e], obs) => {
        if (!e.isIntersecting) return;
        obs.disconnect();
        const start = performance.now();
        const tick = (now) => {
            const p = Math.min(1, (now - start) / 1400);
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 4))).toLocaleString('de-DE') + suffix;
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }).observe(el);
});

const boot = () => { observeReveals(); counters(); };
document.addEventListener('DOMContentLoaded', boot);
document.addEventListener('livewire:navigated', boot);

// Lichtkegel auf Karten und 3D-Neigung: ein einziger, passiver Listener (Event-Delegation)
if (finePointer && !reduced) {
    let frame = 0;
    document.addEventListener('pointermove', (e) => {
        if (frame) return;
        frame = requestAnimationFrame(() => {
            frame = 0;
            const card = e.target.closest?.('.fx-card, .fx-spotlight, .fx-tilt');
            if (!card) return;
            const r = card.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width;
            const y = (e.clientY - r.top) / r.height;
            card.style.setProperty('--mx', `${x * 100}%`);
            card.style.setProperty('--my', `${y * 100}%`);
            if (card.classList.contains('fx-tilt')) {
                card.style.setProperty('--ry', `${(x - 0.5) * 8}deg`);
                card.style.setProperty('--rx', `${(0.5 - y) * 8}deg`);
            }
        });
    }, { passive: true });
    document.addEventListener('pointerout', (e) => {
        const card = e.target.closest?.('.fx-tilt');
        if (card && !card.contains(e.relatedTarget)) { card.style.setProperty('--rx', '0deg'); card.style.setProperty('--ry', '0deg'); }
    }, { passive: true });

    // Parallax im Hero: <div data-parallax="0.3">
    const layers = document.querySelectorAll('[data-parallax]');
    if (layers.length) {
        let ticking = false;
        window.addEventListener('scroll', () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(() => {
                const y = window.scrollY;
                if (y < window.innerHeight * 1.2) layers.forEach((l) => { l.style.transform = `translate3d(0, ${y * Number(l.dataset.parallax)}px, 0)`; });
                ticking = false;
            });
        }, { passive: true });
    }
}

// Produktbild „fliegt“ beim Klick auf eine Karte in die Detailseite (View Transitions API)
document.addEventListener('click', (e) => {
    const link = e.target.closest?.('a[data-vt-product]');
    if (!link || reduced) return;
    const img = link.closest('article')?.querySelector('img');
    if (img) img.style.viewTransitionName = 'product-hero';
});
window.addEventListener('pageshow', () => document.querySelectorAll('img[style*="view-transition-name"]').forEach((i) => { i.style.viewTransitionName = ''; }));

// Hero-Video erst nach dem Laden nachladen (Poster bzw. Headline ist das LCP-Element)
window.addEventListener('load', () => {
    const video = document.querySelector('video[data-lazy-hero]');
    if (!video) return;
    const saveData = navigator.connection && navigator.connection.saveData;
    if (saveData || reduced || window.matchMedia('(max-width: 767px)').matches) return;
    (window.requestIdleCallback || setTimeout)(() => {
        video.querySelectorAll('source[data-src]').forEach((s) => { s.src = s.dataset.src; });
        video.load();
        video.play().catch(() => {});
        video.addEventListener('playing', () => video.classList.remove('opacity-0'), { once: true });
    }, { timeout: 2000 });
});
