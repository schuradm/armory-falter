<?php

namespace App\Filament\Support;

use App\Filament\Resources;

/** Welches Recht welche Backend-Ressource freischaltet (fehlt ein Eintrag, sieht sie nur der Super-Admin). */
final class ResourcePermissions
{
    public const MAP = [
        // Sortiment
        Resources\ProductResource::class => 'catalog.manage',
        Resources\CategoryResource::class => 'catalog.manage',
        Resources\ManufacturerResource::class => 'catalog.manage',
        Resources\CaliberResource::class => 'catalog.manage',
        Resources\LegalClassResource::class => 'catalog.manage',
        Resources\DiscountCodeResource::class => 'catalog.manage',
        // Bestellungen und Kundenservice
        Resources\OrderResource::class => 'orders.manage',
        Resources\WithdrawalResource::class => 'orders.manage',
        Resources\GiftVoucherResource::class => 'orders.manage',
        Resources\ContactMessageResource::class => 'orders.manage',
        Resources\AppointmentResource::class => 'orders.manage',
        Resources\UserResource::class => 'orders.manage',
        // Inhalte
        Resources\PageResource::class => 'content.manage',
        Resources\BlogPostResource::class => 'content.manage',
        Resources\BlogCategoryResource::class => 'content.manage',
        Resources\AuthorResource::class => 'content.manage',
        Resources\CommentResource::class => 'content.manage',
        Resources\FaqResource::class => 'content.manage',
        Resources\EventResource::class => 'content.manage',
        Resources\LocalPageResource::class => 'content.manage',
        Resources\TestimonialResource::class => 'content.manage',
        Resources\RedirectResource::class => 'content.manage',
        Resources\NewsletterCampaignResource::class => 'content.manage',
        Resources\NewsletterSubscriberResource::class => 'content.manage',
        Resources\ProductReviewResource::class => 'content.manage',
        Resources\WorkshopServiceResource::class => 'content.manage',
        // Ankauf / Verkaufsservice
        Resources\TradeRequestResource::class => 'trade.manage',
        // Rechtlich sensible Bereiche
        Resources\WeaponBookEntryResource::class => 'compliance.release',
        Resources\CustomerDocumentResource::class => 'compliance.release',
        Resources\AuditLogResource::class => 'audit.view',
        // Einstellungen
        Resources\ShippingMethodResource::class => 'settings.manage',
        Resources\TaxRateResource::class => 'settings.manage',
    ];

    public static function for(string $resource): ?string
    {
        return self::MAP[$resource] ?? null;
    }
}
