<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\LocalPage;
use App\Models\Product;
use App\Support\Seo;

/** Events, Premium-Club „Elite“ und regionale Landingpages. */
class MarketingController extends Controller
{
    public function events(Seo $seo)
    {
        $seo->title('Events, Testtage & Workshops')
            ->description('Shooting Day, Testtage, Jagdabende, Optik-Workshops und Wiederlade-Seminare bei Armory Falter. Jetzt Platz sichern.')
            ->breadcrumb('Events', route('events.index'));

        return view('marketing.events', ['events' => Event::upcoming()->get()]);
    }

    public function event(Event $event, Seo $seo)
    {
        abort_unless($event->is_published, 404);
        $seo->title($event->title.' – '.$event->starts_at->format('d.m.Y'))->description($event->teaser)
            ->breadcrumb('Events', route('events.index'))->breadcrumb($event->title, route('events.show', $event))
            ->schema(array_filter([
                '@type' => 'Event', 'name' => $event->title, 'description' => $event->teaser,
                'startDate' => $event->starts_at->toIso8601String(), 'endDate' => $event->ends_at?->toIso8601String(),
                'eventStatus' => 'https://schema.org/EventScheduled', 'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'location' => ['@type' => 'Place', 'name' => $event->location ?: config('shop.company.name'), 'address' => config('shop.company.street').', '.config('shop.company.postal_code').' '.config('shop.company.city')],
                'organizer' => ['@id' => url('/').'#organization'],
                'offers' => ['@type' => 'Offer', 'price' => number_format(($event->price_gross ?? 0) / 100, 2, '.', ''), 'priceCurrency' => 'EUR', 'url' => route('events.show', $event),
                    'availability' => $event->seatsLeft() === 0 ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock'],
            ]));

        return view('marketing.event', compact('event'));
    }

    public function elite(Seo $seo)
    {
        $seo->title('Armory Falter Elite – der Club für Jäger & Sportschützen')
            ->description('Frühzugriff auf Neuheiten, exklusive Waffen, VIP-Angebote, Eventeinladungen und Schießstandtage. Jetzt auf die Warteliste.')
            ->breadcrumb('Elite', route('elite'));

        return view('marketing.elite');
    }

    public function local(LocalPage $page, Seo $seo)
    {
        abort_unless($page->is_published, 404);
        $seo->title($page->title)->description($page->meta_description)
            ->breadcrumb('Waffenhändler '.$page->city, route('local.show', $page));

        return view('marketing.local', [
            'page' => $page,
            'products' => Product::visible()->where('is_featured', true)->with(['manufacturer', 'caliber', 'legalClass', 'taxRate', 'media', 'variants'])->take(4)->get(),
        ]);
    }
}
