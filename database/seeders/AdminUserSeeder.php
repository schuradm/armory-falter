<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Admin-Konto für /kms-login. Passwort aus ADMIN_PASSWORD; fehlt es, wird beim ersten Anlegen
 * ein Zufallspasswort erzeugt und einmalig auf der Konsole ausgegeben. Ein bestehendes Passwort
 * wird nur überschrieben, wenn ADMIN_PASSWORD gesetzt ist. 2FA richtet der Admin beim ersten Login ein.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@armory-falter.de');
        $envPassword = env('ADMIN_PASSWORD');

        $user = User::withTrashed()->where('email', $email)->first() ?? new User(['email' => $email]);
        $generated = null;

        $user->fill(['first_name' => 'Shop', 'last_name' => 'Administrator', 'salutation' => 'none', 'customer_type' => 'private']);
        if ($envPassword) {
            $user->password = $envPassword;
        } elseif (! $user->exists) {
            $user->password = $generated = Str::password(20, symbols: false);
        }

        $user->forceFill([
            'customer_number' => $user->customer_number ?? 'A'.now()->format('y').'000001',
            'is_staff' => true,
            'is_active' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'deleted_at' => null,
        ])->save();

        $user->syncRoles(['super-admin']);

        if ($generated) {
            $this->command?->warn("Admin angelegt: {$email} / Passwort: {$generated}  (bitte sofort ändern und 2FA aktivieren)");
        } else {
            $this->command?->info("Admin aktualisiert: {$email}".($envPassword ? ' (Passwort aus ADMIN_PASSWORD gesetzt)' : ' (Passwort unverändert)'));
        }
    }
}
