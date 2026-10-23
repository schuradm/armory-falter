<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\WorkshopService;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

/** Landingpages: Verkaufsservice (egun), Ankauf, Werkstatt, Beratung. */
class ServiceController extends Controller
{
    public function consignment(Seo $seo): View
    {
        $seo->title('Waffe verkaufen lassen – egun-Verkaufsservice')
            ->description('Wir verkaufen Ihre Waffe professionell über egun: Bewertung, Fotos, Einstellung, Abwicklung und Versand an Berechtigte. Jetzt Waffe anmelden.')
            ->breadcrumb('Verkaufsservice', route('trade.consignment'));

        return view('services.consignment', ['faqs' => Faq::where('group_name', 'Verkaufsservice')->orderBy('sort_order')->get()]);
    }

    public function buyback(Seo $seo): View
    {
        $seo->title('Wir kaufen Waffen – Ankauf zu fairen Preisen')
            ->description('Waffen-Ankauf durch Armory Falter: Jagdwaffen, Sportwaffen, Kurzwaffen, Optiken und Sammlungen. Schnelle Bewertung, sofortige Auszahlung, rechtssichere Abwicklung.')
            ->breadcrumb('Waffen-Ankauf', route('trade.buyback'));

        return view('services.buyback', ['faqs' => Faq::where('group_name', 'Ankauf')->orderBy('sort_order')->get()]);
    }

    public function workshop(Seo $seo): View
    {
        $seo->title('Büchsenmacher-Werkstatt: Reinigung, Optikmontage, Cerakote')
            ->description('Werkstatt von Armory Falter: Waffenreinigung, Waffenpflege, Montage von Zielfernrohren, Einschießen, Reparaturen und Cerakote-Beschichtung. Termin online buchen.')
            ->breadcrumb('Werkstatt', route('workshop.index'));

        return view('services.workshop', ['services' => WorkshopService::orderBy('sort_order')->get()]);
    }

    public function workshopService(WorkshopService $service, Seo $seo): View
    {
        $seo->title($service->name.' – Werkstatt')->description($service->teaser)
            ->breadcrumb('Werkstatt', route('workshop.index'))->breadcrumb($service->name, route('workshop.show', $service))
            ->schema(['@type' => 'Service', 'name' => $service->name, 'provider' => ['@id' => url('/').'#organization'], 'areaServed' => 'DE',
                'offers' => $service->price_from_gross ? ['@type' => 'Offer', 'priceCurrency' => 'EUR', 'price' => number_format($service->price_from_gross / 100, 2, '.', '')] : null]);

        return view('services.workshop-show', compact('service'));
    }

    public function booking(Seo $seo, string $type = 'workshop'): View
    {
        $seo->title($type === 'consultation' ? 'Beratungstermin buchen' : 'Werkstatt-Termin buchen')->noindex();

        return view('services.booking', ['type' => $type]);
    }
}
