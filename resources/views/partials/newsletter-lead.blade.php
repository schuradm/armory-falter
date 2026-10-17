{{-- Leadmagnet „Waffenratgeber“: normales Formular (POST), funktioniert auch bevor JavaScript geladen ist --}}
@php($errs = $errors->getBag('newsletter_lead'))
<div id="ratgeber" class="scroll-mt-28">
    @if (session('newsletter_done') === 'lead')
        <p class="flex items-start gap-2 rounded-md bg-white/5 p-4 text-sm text-white/85" role="status"><x-icon name="mail" class="mt-0.5 size-5 shrink-0 text-brand-400"/> Fast geschafft! Wir haben Ihnen eine E-Mail geschickt. Bitte bestätigen Sie darin Ihre Adresse, direkt danach erhalten Sie den Download-Link zum Waffenratgeber.</p>
    @else
        <form method="post" action="{{ route('newsletter.subscribe') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="variant" value="lead">
            <div class="flex flex-col gap-2 sm:flex-row">
                <label for="lead-email" class="sr-only">E-Mail-Adresse</label>
                <input id="lead-email" name="email" type="email" value="{{ old('email') }}" placeholder="Ihre E-Mail-Adresse" required autocomplete="email" @if ($errs->has('email')) aria-invalid="true" aria-describedby="lead-email-error" @endif class="block w-full rounded-md border-white/15 bg-white/5 py-3.5 text-white placeholder:text-white/55 focus:border-brand-400 focus:ring-brand-400">
                <button type="submit" class="btn-primary fx-shine shrink-0 px-6"><x-icon name="download" class="size-4"/> Gratis anfordern</button>
            </div>
            <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
            <label class="flex items-start gap-2 text-xs text-white/65"><input type="checkbox" name="consent" value="1" required class="mt-0.5 size-5 shrink-0 rounded border-white/20 bg-white/5 text-brand-600 focus:ring-brand-400">
                <span>Ja, ich möchte den Waffenratgeber und den Newsletter von Armory Falter mit Angeboten, Neuheiten und Fachartikeln per E-Mail erhalten. Abmeldung jederzeit über den Link in jeder E-Mail. Details in der <a href="{{ route('page.show', 'datenschutz') }}" class="underline">Datenschutzerklärung</a>.</span></label>
            @if ($errs->has('email'))<p id="lead-email-error" class="text-xs text-red-300">{{ $errs->first('email') }}</p>@endif
            @if ($errs->has('consent'))<p class="text-xs text-red-300">{{ $errs->first('consent') }}</p>@endif
        </form>
    @endif
</div>
