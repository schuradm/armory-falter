<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Mitarbeiter müssen die Zwei-Faktor-Anmeldung aktiviert haben, bevor sie den Admin-Bereich nutzen. */
class RequireStaffTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->two_factor_confirmed_at && ! app()->environment('local', 'testing')) {
            return redirect()->route('account.security')->with('status', 'Bitte aktivieren Sie die Zwei-Faktor-Anmeldung, um den Admin-Bereich zu nutzen.');
        }

        return $next($request);
    }
}
