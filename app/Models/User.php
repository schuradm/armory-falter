<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'customer_type', 'salutation', 'first_name', 'last_name', 'company_name', 'vat_id',
        'email', 'phone', 'birth_date', 'password',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'age_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'is_staff' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->customer_number ??= 'K'.now()->format('y').str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        });
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim("{$this->first_name} {$this->last_name}"));
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_staff && $this->is_active && $this->hasVerifiedEmail();
    }

    /** Alter in Jahren, nur wenn das Geburtsdatum durch ein geprüftes Dokument bestätigt wurde. */
    public function verifiedAge(): ?int
    {
        if (! $this->age_verified_at || ! $this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }

    public function isAuthorityOrDealer(): bool
    {
        return in_array($this->customer_type, ['authority', 'dealer'], true);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(CustomerEntitlement::class);
    }

    public function activeEntitlements(): HasMany
    {
        return $this->entitlements()->active();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function defaultWishlist(): Wishlist
    {
        return $this->wishlists()->firstOrCreate(['is_default' => true], ['name' => 'Favoriten']);
    }

    public function tradeRequests(): HasMany
    {
        return $this->hasMany(TradeRequest::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
