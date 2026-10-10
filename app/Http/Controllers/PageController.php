<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Page;
use App\Support\Seo;

class PageController extends Controller
{
    public const SLUGS = ['impressum', 'datenschutz', 'agb', 'widerruf', 'versandinformationen', 'zahlungsarten', 'barrierefreiheit', 'ueber-uns'];

    public function show(string $slug, Seo $seo)
    {
        $page = Page::where('slug', $slug)->where('status', 'published')->with('seo')->firstOrFail();
        $seo->title($page->title)->description(strip_tags($page->body))->breadcrumb($page->title, route('page.show', $slug))
            ->applyOverrides($page->seo);

        return view('pages.show', compact('page'));
    }

    public function faq(Seo $seo)
    {
        $seo->title('Häufige Fragen (FAQ)')->description('Antworten zu Bestellung, Waffenerwerb, WBK, Munition, Versand, Zahlung, Werkstatt und Ankauf bei Armory Falter.')
            ->breadcrumb('FAQ', route('faq'));
        $groups = Faq::whereNull('faqable_type')->orderBy('sort_order')->get()->groupBy('group_name');

        return view('pages.faq', compact('groups'));
    }

    public function contact(Seo $seo)
    {
        $seo->title('Kontakt & Anfahrt')->description('Kontakt zu Armory Falter in Wolfschlugen: Telefon, E-Mail, Öffnungszeiten und Anfahrt.')
            ->breadcrumb('Kontakt', route('contact'))->schema(Seo::organization());

        return view('pages.contact');
    }
}
