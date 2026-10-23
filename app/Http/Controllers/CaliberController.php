<?php

namespace App\Http\Controllers;

use App\Models\Caliber;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

class CaliberController extends Controller
{
    public function show(Caliber $caliber, Seo $seo): View
    {
        $seo->title("Kaliber {$caliber->name}: Waffen & Munition")
            ->description("Waffen und Munition im Kaliber {$caliber->name} bei Armory Falter. Abgabe erlaubnispflichtiger Artikel nur an Berechtigte.")
            ->canonical(route('calibers.show', $caliber))
            ->breadcrumb('Kaliber '.$caliber->name, route('calibers.show', $caliber));

        return view('calibers.show', compact('caliber'));
    }
}
