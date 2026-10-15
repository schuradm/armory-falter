<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rollen und Rechte (spatie/laravel-permission).
 * super-admin darf alles (Gate::before in AppServiceProvider), braucht also keine Einzelrechte.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** Recht => Beschreibung (nur Doku, spatie speichert nur den Namen). */
    public const PERMISSIONS = [
        'compliance.release' => 'Erlaubnispflichtige Bestellungen freigeben/zurückhalten/ablehnen (§ 34 WaffG)',
        'documents.review' => 'Kundendokumente (WBK, Jagdschein, Ausweis) einsehen und prüfen',
        'orders.manage' => 'Bestellungen bearbeiten, Zahlung und Versand buchen',
        'catalog.manage' => 'Produkte, Kategorien, Hersteller, Kaliber pflegen',
        'content.manage' => 'Seiten, Blog, FAQ, Events, Regionalseiten, Kundenstimmen pflegen',
        'trade.manage' => 'Ankauf- und Verkaufsservice-Anfragen bearbeiten',
        'settings.manage' => 'Shop-Einstellungen und Zahlarten ändern',
        'audit.view' => 'Audit-Log einsehen',
    ];

    public const ROLES = [
        'customer' => [],
        'super-admin' => [], // alle Rechte über Gate::before
        'compliance' => ['compliance.release', 'documents.review', 'orders.manage', 'trade.manage', 'audit.view'],
        'shop-manager' => ['orders.manage', 'catalog.manage', 'content.manage', 'trade.manage', 'settings.manage'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::PERMISSIONS) as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (self::ROLES as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
