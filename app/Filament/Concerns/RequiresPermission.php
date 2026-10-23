<?php

namespace App\Filament\Concerns;

use App\Filament\Support\ResourcePermissions;
use Illuminate\Database\Eloquent\Model;

/**
 * Bindet eine Filament-Ressource an ein Recht aus RolesAndPermissionsSeeder.
 * Ohne Policy erlaubt Filament sonst jede Aktion für jede Mitarbeiterrolle.
 * Super-Admins kommen über Gate::before immer durch.
 */
trait RequiresPermission
{
    public static function hasRequiredPermission(): bool
    {
        $permission = ResourcePermissions::for(static::class);

        return $permission !== null && (bool) auth()->user()?->can($permission);
    }

    public static function can(string $action, ?Model $record = null): bool
    {
        return static::hasRequiredPermission() && parent::can($action, $record);
    }
}
