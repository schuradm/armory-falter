<?php

namespace App\Support;

use App\Models\SeoMeta;
use Illuminate\Support\Str;

/**
 * Sammelt Meta-Daten und Schema.org-Blöcke für die aktuelle Seite (Singleton, im Layout gerendert).
 */
class Seo
{
    public string $title = '';
    public string $description = '';
    public ?string $canonical = null;
    public string $robots = 'index,follow';
    public ?string $image = null;
    public string $type = 'website';
    /** @var list<array{name:string,url:string}> */
    public array $breadcrumbs = [];
    /** @var list<array> */
    public array $jsonLd = [];

    public function title(string $title, bool $suffix = true): static
    {
        $this->title = $suffix ? Str::limit($title, 55, '').' | Armory Falter' : $title;

        return $this;
    }

    public function description(?string $text): static
    {
        $this->description = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $text))), 158);

        return $this;
    }

    public function canonical(?string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    public function noindex(): static
    {
        $this->robots = 'noindex,follow';

        return $this;
    }

    public function image(?string $url): static
    {
        $this->image = $url;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function breadcrumb(string $name, string $url): static
    {
        $this->breadcrumbs[] = ['name' => $name, 'url' => $url];

        return $this;
    }

    public function schema(array $data): static
    {
        $this->jsonLd[] = ['@context' => 'https://schema.org'] + $data;

        return $this;
    }

    /** Pflegbare Overrides aus dem Admin (Tabelle seo_meta) anwenden. */
    public function applyOverrides(?SeoMeta $meta): static
    {
        if (! $meta) {
            return $this;
        }
        if ($meta->meta_title) {
            $this->title = $meta->meta_title;
        }
        if ($meta->meta_description) {
            $this->description = $meta->meta_description;
        }
        if ($meta->canonical_url) {
            $this->canonical = $meta->canonical_url;
        }
        if ($meta->robots) {
            $this->robots = $meta->robots;
        }
        if ($meta->og_image_path) {
            $this->image = asset('storage/'.$meta->og_image_path);
        }

        return $this;
    }

    public function allJsonLd(): array
    {
        $blocks = $this->jsonLd;
        if (count($this->breadcrumbs) > 0) {
            $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Startseite', 'item' => url('/')]];
            foreach ($this->breadcrumbs as $i => $b) {
                $items[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $b['name'], 'item' => $b['url']];
            }
            $blocks[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
        }

        return $blocks;
    }

    public static function organization(): array
    {
        $c = config('shop.company');
        $days = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

        return [
            '@context' => 'https://schema.org',
            '@type' => ['Store', 'LocalBusiness'],
            '@id' => url('/').'#organization',
            'name' => $c['name'],
            'url' => url('/'),
            'logo' => asset(config('shop.logo.path')),
            'image' => asset('images/og-default.jpg'),
            'telephone' => $c['phone'],
            'email' => $c['email'],
            'founder' => ['@type' => 'Person', 'name' => $c['owner']],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $c['street'],
                'postalCode' => $c['postal_code'],
                'addressLocality' => $c['city'],
                'addressRegion' => 'Baden-Württemberg',
                'addressCountry' => 'DE',
            ],
            'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $c['geo']['lat'], 'longitude' => $c['geo']['lng']],
            'openingHoursSpecification' => collect($c['opening_hours'])->map(fn ($h, $d) => [
                '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days[$d], 'opens' => $h[0], 'closes' => $h[1],
            ])->values()->all(),
            'sameAs' => array_values(array_filter([$c['social']['instagram'], $c['social']['youtube'], $c['social']['facebook']])),
        ];
    }
}
