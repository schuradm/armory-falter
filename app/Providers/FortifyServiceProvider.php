<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Services\AuditLogger;
use App\Services\Cart\CartService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return [Limit::perMinute(5)->by($key), Limit::perHour(30)->by($request->ip())];
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));

        Event::listen(Login::class, function (Login $event) {
            app(CartService::class)->mergeGuestCartInto($event->user, request()->cookie(CartService::COOKIE) ?? session(CartService::COOKIE));
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            app(AuditLogger::class)->log('auth.login', $event->user);
        });

        Event::listen(Failed::class, function (Failed $event) {
            app(AuditLogger::class)->log('auth.failed', $event->user, new: ['email' => Str::mask((string) ($event->credentials['email'] ?? ''), '*', 3)], actorType: 'system');
        });
    }
}
