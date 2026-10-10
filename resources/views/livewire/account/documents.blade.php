<div class="space-y-8">
    <div>
        <h1 class="font-display text-2xl font-extrabold uppercase">Dokumente & Berechtigungen</h1>
        <p class="mt-2 max-w-2xl text-sm text-muted">Für altersbeschränkte und erlaubnispflichtige Artikel benötigen wir geprüfte Nachweise. Ihre Dateien werden verschlüsselt gespeichert, nur von unserer Waffensachbearbeitung eingesehen und nach Ablauf der Aufbewahrungsfrist gelöscht.</p>
    </div>

    <section class="grid gap-4 sm:grid-cols-2">
        <div class="card p-5">
            <p class="label">Altersverifikation</p>
            @if ($user->age_verified_at)
                <p class="flex items-center gap-2 font-medium text-emerald-400"><x-icon name="check" class="size-5"/> Bestätigt ({{ $user->verifiedAge() }} Jahre)</p>
            @else
                <p class="font-medium text-amber-400">Noch nicht bestätigt</p>
                <p class="mt-1 text-xs text-muted">Laden Sie Personalausweis, Reisepass, WBK oder Jagdschein hoch.</p>
            @endif
        </div>
        <div class="card p-5">
            <p class="label">Aktive Erwerbsberechtigungen</p>
            @forelse ($entitlements as $e)
                <p class="mt-1 flex items-start gap-2 text-sm"><x-icon name="shield" class="mt-0.5 size-4 text-accent"/>{{ $e->describe() }}</p>
            @empty
                <p class="text-sm text-muted">Keine. Nach Prüfung Ihrer WBK oder Ihres Jagdscheins erscheinen sie hier.</p>
            @endforelse
        </div>
    </section>

    <section class="card p-6">
        <h2 class="font-display text-lg font-bold">Neues Dokument hochladen</h2>
        <form wire:submit="save" class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label for="doc-type" class="label">Dokumentart *</label>
                <select id="doc-type" wire:model="type" class="input">
                    @foreach (\App\Enums\DocumentType::cases() as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="doc-valid" class="label">Gültig bis</label>
                <input id="doc-valid" type="date" wire:model="validUntil" class="input">
                <p class="mt-1 text-xs text-muted">Jagdschein: Ende des Jagdjahres (31.03.)</p>
                @error('validUntil')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <div><label for="doc-nr" class="label">Dokumentnummer</label><input id="doc-nr" wire:model="documentNumber" class="input" autocomplete="off"></div>
            <div><label for="doc-auth" class="label">Ausstellende Behörde</label><input id="doc-auth" wire:model="issuingAuthority" class="input" placeholder="z. B. Landratsamt Esslingen"></div>
            <div class="sm:col-span-2" x-data="{ dragging: false }">
                <label class="label" for="doc-file">Datei (PDF, JPG, PNG, max. 10 MB) *</label>
                <label for="doc-file" class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-card border-2 border-dashed p-8 text-center text-sm transition"
                       :class="dragging ? 'border-accent bg-brand/5' : 'border-line'" @dragover.prevent="dragging = true" @dragleave="dragging = false" @drop="dragging = false">
                    <x-icon name="upload" class="size-8 text-accent"/>
                    <span>@if ($file) {{ $file->getClientOriginalName() }} @else Datei auswählen oder hierher ziehen @endif</span>
                    <span class="text-xs text-muted">Bei WBK bitte alle beschriebenen Seiten, bei Ausweisen Vorder- und Rückseite.</span>
                    <input id="doc-file" type="file" wire:model="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="sr-only">
                </label>
                <div wire:loading wire:target="file" class="mt-2 text-xs text-muted">Wird hochgeladen …</div>
                @error('file')<p class="mt-1 text-xs text-red-400">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-start gap-2 text-sm sm:col-span-2"><input type="checkbox" wire:model="confirm" class="mt-0.5 rounded border-line bg-surface-2 text-accent">
                <span>Das Dokument ist auf mich ausgestellt und gültig. Ich bin mit der Verarbeitung zur Prüfung meiner Erwerbsberechtigung einverstanden (siehe <a href="{{ route('page.show', 'datenschutz') }}" class="text-accent underline">Datenschutz</a>).</span></label>
            @error('confirm')<p class="text-xs text-red-400 sm:col-span-2">{{ $message }}</p>@enderror
            <div class="sm:col-span-2"><button type="submit" class="btn-primary" wire:loading.attr="disabled">Hochladen</button></div>
        </form>
    </section>

    <section>
        <h2 class="font-display text-lg font-bold">Hochgeladene Dokumente</h2>
        <div class="card mt-4 divide-y divide-line">
            @forelse ($documents as $doc)
                <div class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm">
                    <div class="flex items-center gap-3"><x-icon name="file" class="size-5 text-muted"/>
                        <div><p class="font-medium">{{ $doc->type->label() }}</p><p class="text-xs text-muted">Hochgeladen am {{ $doc->created_at->format('d.m.Y') }}@if($doc->valid_until) · gültig bis {{ $doc->valid_until->format('d.m.Y') }}@endif</p></div>
                    </div>
                    <div class="text-right">
                        <span @class(['badge', 'bg-emerald-500/15 text-emerald-300' => $doc->status->value === 'approved', 'bg-red-500/15 text-red-300' => in_array($doc->status->value, ['rejected', 'expired', 'revoked']), 'bg-surface-2 text-muted' => $doc->status->awaitingReview()])>
                            {{ $doc->status->awaitingReview() ? 'In Prüfung' : $doc->status->label() }}</span>
                        @if ($doc->rejection_reason)<p class="mt-1 max-w-xs text-xs text-red-300">{{ $doc->rejection_reason }}</p>@endif
                    </div>
                </div>
            @empty
                <p class="p-6 text-sm text-muted">Noch keine Dokumente hochgeladen.</p>
            @endforelse
        </div>
    </section>
</div>
