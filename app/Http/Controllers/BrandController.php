<?php

namespace App\Http\Controllers;

use App\Models\Manufacturer;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class BrandController extends Controller
{
    public function index(Seo $seo): View
    {
        $seo->title('Hersteller & Marken A–Z')->description('Alle Marken bei Armory Falter: Glock, CZ, SIG Sauer, Heckler & Koch, Walther, Beretta, Mauser, Blaser, Steiner, Zeiss, Leica und mehr.')
            ->breadcrumb('Hersteller', route('brands.index'));

        $brands = Manufacturer::orderBy('name')->withCount(['products' => fn ($q) => $q->visible()])->get()
            ->groupBy(fn ($m) => mb_strtoupper(mb_substr($m->name, 0, 1)));

        return view('brands.index', compact('brands'));
    }

    public function show(Manufacturer $manufacturer, Seo $seo): View
    {
        $manufacturer->load('faqs', 'seo');
        $seo->title("{$manufacturer->name} kaufen – Waffen & Zubehör")
            ->description($manufacturer->intro ?: "{$manufacturer->name} beim autorisierten Fachhändler Armory Falter: aktuelle Modelle, Beratung und Service.")
            ->canonical(route('brands.show', $manufacturer))
            ->breadcrumb('Hersteller', route('brands.index'))
            ->breadcrumb($manufacturer->name, route('brands.show', $manufacturer))
            ->schema(['@type' => 'Brand', 'name' => $manufacturer->name, 'url' => route('brands.show', $manufacturer)])
            ->applyOverrides($manufacturer->seo);

        return view('brands.show', compact('manufacturer'));
    }
}
