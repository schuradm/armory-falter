<x-layouts.account active="profile">
    <h1 class="font-display text-2xl font-extrabold uppercase">Persönliche Daten</h1>
    <form method="POST" action="{{ route('user-profile-information.update') }}" class="card mt-6 grid gap-4 p-6 sm:grid-cols-2">
        @csrf @method('PUT')
        <div><label class="label" for="fn">Vorname</label><input id="fn" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="input" required></div>
        <div><label class="label" for="ln">Nachname</label><input id="ln" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="input" required></div>
        <div><label class="label" for="co">Firma</label><input id="co" name="company_name" value="{{ old('company_name', $user->company_name) }}" class="input"></div>
        <div><label class="label" for="ph">Telefon</label><input id="ph" name="phone" value="{{ old('phone', $user->phone) }}" class="input"></div>
        <div><label class="label" for="em">E-Mail</label><input id="em" type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required></div>
        <div><label class="label" for="bd">Geburtsdatum</label>
            @if ($user->age_verified_at)<p class="py-2 text-sm">{{ $user->birth_date?->format('d.m.Y') }} <span class="text-xs text-muted">(verifiziert, Änderung nur über uns)</span></p>
            @else<input id="bd" type="date" name="birth_date" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}" class="input">@endif</div>
        @if ($errors->updateProfileInformation->any())<div class="text-sm text-red-400 sm:col-span-2">{{ $errors->updateProfileInformation->first() }}</div>@endif
        @if (session('status') === 'profile-information-updated')<p class="text-sm text-emerald-400 sm:col-span-2">Gespeichert.</p>@endif
        <div class="sm:col-span-2"><button class="btn-primary">Speichern</button></div>
    </form>
    <h2 class="mt-10 font-display text-lg font-bold">Adressen</h2>
    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        @forelse ($user->addresses as $a)
            <div class="card p-5 text-sm"><p class="label">{{ $a->type === 'billing' ? 'Rechnungsadresse' : 'Lieferadresse' }}</p><p>{{ $a->oneLine() }}</p></div>
        @empty
            <p class="text-sm text-muted">Adressen werden beim ersten Checkout gespeichert.</p>
        @endforelse
    </div>
</x-layouts.account>
