<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel as FilamentLabel;

enum Availability: string implements FilamentLabel
{
    use HasLabel;

    case InStock = 'in_stock';
    case LowStock = 'low_stock';
    case OnOrder = 'on_order';
    case Preorder = 'preorder';
    case OutOfStock = 'out_of_stock';
    case InStoreOnly = 'in_store_only';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'Auf Lager',
            self::LowStock => 'Nur noch wenige verfügbar',
            self::OnOrder => 'Lieferbar auf Bestellung',
            self::Preorder => 'Vorbestellbar',
            self::OutOfStock => 'Ausverkauft',
            self::InStoreOnly => 'Nur im Ladengeschäft',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InStock => 'text-emerald-400',
            self::LowStock, self::OnOrder, self::Preorder => 'text-amber-400',
            self::OutOfStock, self::InStoreOnly => 'text-red-400',
        };
    }

    public function schemaOrg(): string
    {
        return match ($this) {
            self::InStock, self::LowStock => 'https://schema.org/InStock',
            self::OnOrder => 'https://schema.org/BackOrder',
            self::Preorder => 'https://schema.org/PreOrder',
            self::OutOfStock => 'https://schema.org/OutOfStock',
            self::InStoreOnly => 'https://schema.org/InStoreOnly',
        };
    }

    public function purchasable(): bool
    {
        return ! in_array($this, [self::OutOfStock, self::InStoreOnly], true);
    }
}
