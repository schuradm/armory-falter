{{-- Eigenes Consent-Banner (TTDSG § 25 / DSGVO). Speichert die Wahl im Cookie af_consent und protokolliert sie serverseitig.
     Optionale Dienste werden erst nach Einwilligung nachgeladen (Event "consent-updated"). Google Consent Mode v2 wird
     mit "denied" initialisiert und nur bei Zustimmung auf "granted" gesetzt. --}}
<div x-data="cookieConsent()" x-init="init()" @open-consent.window="open = true; details = true">
    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-x-0 bottom-0 z-[70] p-3 sm:p-6" role="dialog" aria-modal="false" aria-labelledby="consent-title">
        <div class="card mx-auto max-w-3xl p-5 sm:p-6">
            <h2 id="consent-title" class="font-display text-lg font-bold">Ihre Privatsphäre</h2>
            <p class="mt-2 text-sm text-muted">Wir verwenden technisch notwendige Cookies für Warenkorb, Login und Sicherheit. Mit Ihrer Einwilligung nutzen wir zusätzlich Statistik-Dienste, um den Shop zu verbessern, und externe Medien (z. B. Videos). Sie können Ihre Auswahl jederzeit unter „Cookie-Einstellungen“ im Fußbereich ändern. Mehr in der <a href="{{ route('page.show', 'datenschutz') }}" class="text-accent underline">Datenschutzerklärung</a>.</p>
            <div x-show="details" x-collapse class="mt-4 space-y-3 text-sm">
                <label class="flex items-start gap-3"><input type="checkbox" checked disabled class="mt-1 rounded border-line bg-surface-2 text-accent"> <span><strong>Notwendig</strong><br><span class="text-muted">Sitzung, Warenkorb, Sicherheit (Cloudflare Turnstile), Ihre Consent-Auswahl.</span></span></label>
                <label class="flex items-start gap-3"><input type="checkbox" x-model="statistics" class="mt-1 rounded border-line bg-surface-2 text-accent"> <span><strong>Statistik</strong><br><span class="text-muted">Anonymisierte Reichweitenmessung (z. B. Matomo).</span></span></label>
                <label class="flex items-start gap-3"><input type="checkbox" x-model="media" class="mt-1 rounded border-line bg-surface-2 text-accent"> <span><strong>Externe Medien</strong><br><span class="text-muted">Eingebettete YouTube-Videos (youtube-nocookie.com).</span></span></label>
                <label class="flex items-start gap-3"><input type="checkbox" x-model="marketing" class="mt-1 rounded border-line bg-surface-2 text-accent"> <span><strong>Marketing</strong><br><span class="text-muted">Derzeit nicht im Einsatz.</span></span></label>
            </div>
            <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn-outline" @click="details ? save() : details = true" x-text="details ? 'Auswahl speichern' : 'Einstellungen'"></button>
                <button type="button" class="btn-outline" @click="rejectAll()">Nur notwendige</button>
                <button type="button" class="btn-primary" @click="acceptAll()">Alle akzeptieren</button>
            </div>
        </div>
    </div>
</div>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){ dataLayer.push(arguments); }
    gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'denied', wait_for_update: 500 });

    function cookieConsent() {
        const VERSION = '1';
        return {
            open: false, details: false, statistics: false, media: false, marketing: false,
            init() {
                const raw = document.cookie.split('; ').find((c) => c.startsWith('af_consent='));
                if (!raw) { this.open = true; return; }
                try {
                    const v = JSON.parse(decodeURIComponent(raw.split('=')[1]));
                    if (v.v !== VERSION) { this.open = true; return; }
                    Object.assign(this, { statistics: !!v.statistics, media: !!v.media, marketing: !!v.marketing });
                    this.apply();
                } catch (e) { this.open = true; }
            },
            acceptAll() { this.statistics = this.media = this.marketing = true; this.save(); },
            rejectAll() { this.statistics = this.media = this.marketing = false; this.save(); },
            save() {
                const value = { v: VERSION, id: (crypto.randomUUID ? crypto.randomUUID() : String(Date.now())), statistics: this.statistics, media: this.media, marketing: this.marketing };
                document.cookie = 'af_consent=' + encodeURIComponent(JSON.stringify(value)) + '; Max-Age=' + (60 * 60 * 24 * 180) + '; Path=/; SameSite=Lax; Secure';
                fetch('{{ route('consent.store') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(value) }).catch(() => {});
                this.open = false; this.details = false; this.apply();
            },
            apply() {
                gtag('consent', 'update', { analytics_storage: this.statistics ? 'granted' : 'denied', ad_storage: this.marketing ? 'granted' : 'denied', ad_user_data: this.marketing ? 'granted' : 'denied', ad_personalization: this.marketing ? 'granted' : 'denied' });
                window.afConsent = { statistics: this.statistics, media: this.media, marketing: this.marketing };
                window.dispatchEvent(new CustomEvent('consent-updated', { detail: window.afConsent }));
            },
        };
    }
</script>
