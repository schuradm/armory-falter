<?php

namespace App\Filament\Pages;

use App\Enums\PaymentMethod;
use App\Filament\Support\FormFields;
use App\Models\Setting;
use App\Support\Settings as SettingsStore;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Backend-pflegbare Einstellungen (Tabelle `settings`, gelesen über App\Support\Settings).
 * Gespeichert wird je Bereich ein Schlüssel: home, product_why, community, promo_bar, seo, elite, payment_methods.
 *
 * @property Form $form
 */
class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Einstellungen';

    protected static ?string $title = 'Einstellungen';

    protected static ?string $slug = 'einstellungen';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.settings';

    /** Listen der Startseite (werden als Ganzes gespeichert, siehe Settings::home()). */
    public const HOME_LISTS = ['stats', 'usps', 'steps'];

    /** Linien-Icons aus resources/views/components/icon.blade.php, die als USP-Symbol passen. */
    public const ICONS = [
        'users' => 'Personen', 'user' => 'Person', 'target' => 'Zielscheibe', 'crosshair' => 'Fadenkreuz', 'scope' => 'Zielfernrohr',
        'euro' => 'Euro', 'wrench' => 'Werkzeug', 'shield' => 'Schild', 'truck' => 'Versand', 'medal' => 'Medaille', 'star' => 'Stern',
        'lock' => 'Schloss', 'calendar' => 'Kalender', 'phone' => 'Telefon', 'map-pin' => 'Standort', 'clock' => 'Uhr', 'check' => 'Haken',
        'sparkles' => 'Funkeln', 'bolt' => 'Blitz', 'tree' => 'Baum (Jagd)', 'box' => 'Paket', 'file' => 'Dokument', 'camera' => 'Kamera',
    ];

    public const COMMUNITY = [
        'facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube',
        'whatsapp_channel' => 'WhatsApp-Kanal', 'telegram' => 'Telegram',
    ];

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('settings.manage');
    }

    public function mount(): void
    {
        $this->form->fill(self::loadState(app(SettingsStore::class)));
    }

    /** Aktuelle Werte (Einstellungen mit Fallback auf config/shop.php) in Formularstruktur. */
    public static function loadState(SettingsStore $settings): array
    {
        $home = $settings->home();
        $home['hero']['headline_lines'] = array_values(array_filter(array_map('trim', explode('|', (string) ($home['hero']['headline'] ?? '')))));
        unset($home['hero']['headline']);

        $payments = [];
        foreach (PaymentMethod::cases() as $method) {
            $payments[$method->value] = $method->isEnabled();
        }

        return [
            'home' => $home,
            'product_why' => array_values($settings->get('product_why', config('shop.product_why'))),
            'community' => array_replace(array_fill_keys(array_keys(self::COMMUNITY), null), array_filter((array) config('shop.community')), (array) $settings->get('community', [])),
            'promo_bar' => $settings->get('promo_bar'),
            'seo' => [
                'home_title' => $settings->get('seo.home_title'),
                'home_description' => $settings->get('seo.home_description'),
            ],
            'elite_price' => (int) $settings->get('elite.price', 9900),
            'payment_methods' => $payments,
        ];
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Forms\Components\Tabs::make('settings')->persistTabInQueryString()->tabs([
                $this->homeTab(),
                Forms\Components\Tabs\Tab::make('Warum Armory Falter?')->icon('heroicon-o-check-badge')->schema([
                    Forms\Components\Repeater::make('product_why')->label('Vorteile unter jedem Produkt')
                        ->simple(Forms\Components\TextInput::make('text')->required()->maxLength(120))
                        ->reorderable()->minItems(1)->maxItems(10)->addActionLabel('Vorteil hinzufügen'),
                ]),
                Forms\Components\Tabs\Tab::make('Community-Links')->icon('heroicon-o-share')->schema([
                    Forms\Components\Section::make()->description('Leer = Kanal wird nicht angezeigt.')->columns(2)->schema(
                        collect(self::COMMUNITY)->map(fn ($label, $key) => Forms\Components\TextInput::make("community.{$key}")->label($label)->url()->maxLength(255)->placeholder('https://…'))->values()->all()
                    ),
                ]),
                Forms\Components\Tabs\Tab::make('Promo-Leiste')->icon('heroicon-o-megaphone')->schema([
                    Forms\Components\Textarea::make('promo_bar')->label('Text der Promo-Leiste')->rows(2)->maxLength(300)
                        ->helperText('Erscheint über dem Header auf allen Seiten. Leer = ausgeblendet. Erlaubt: <a href="…">, <strong>, <em>, <br>.'),
                ]),
                Forms\Components\Tabs\Tab::make('SEO Startseite')->icon('heroicon-o-magnifying-glass')->schema([
                    Forms\Components\TextInput::make('seo.home_title')->label('Seitentitel')->maxLength(70)
                        ->placeholder('Armory Falter – Waffenhändler bei Stuttgart für Jagd, Sport & Präzision')->helperText('Max. 70 Zeichen, leer = Standard.'),
                    Forms\Components\Textarea::make('seo.home_description')->label('Meta-Beschreibung')->rows(3)->maxLength(170)
                        ->helperText('Max. 170 Zeichen, leer = Standard.'),
                ]),
                Forms\Components\Tabs\Tab::make('Elite-Club')->icon('heroicon-o-trophy')->schema([
                    FormFields::euro('elite_price', 'Jahresbeitrag (brutto)', true)->helperText('Wird auf der Elite-Club-Seite angezeigt.'),
                ]),
                $this->paymentTab(),
            ]),
        ]);
    }

    private function homeTab(): Forms\Components\Tabs\Tab
    {
        $text = fn (string $name, string $label, int $max = 200) => Forms\Components\TextInput::make($name)->label($label)->maxLength($max);

        return Forms\Components\Tabs\Tab::make('Startseite')->icon('heroicon-o-home')->schema([
            Forms\Components\Section::make('Hero (Attention)')->collapsible()->columns(2)->schema([
                $text('home.hero.eyebrow', 'Dachzeile', 120)->columnSpanFull(),
                Forms\Components\Repeater::make('home.hero.headline_lines')->label('Überschrift (eine Zeile je Eintrag, Zeile 2 in Markenfarbe)')
                    ->simple(Forms\Components\TextInput::make('line')->required()->maxLength(40))
                    ->minItems(1)->maxItems(3)->reorderable()->addActionLabel('Zeile hinzufügen')->columnSpanFull(),
                Forms\Components\Textarea::make('home.hero.text')->label('Text')->rows(3)->maxLength(400)->columnSpanFull(),
                $text('home.hero.cta_primary.label', 'Button 1: Text', 40)->required(),
                $text('home.hero.cta_primary.url', 'Button 1: Link', 255)->required(),
                $text('home.hero.cta_secondary.label', 'Button 2: Text', 40)->required(),
                $text('home.hero.cta_secondary.url', 'Button 2: Link', 255)->required(),
                FormFields::image('home.hero.image', 'Hintergrundbild (optional, 1920 × 1080)', 'home'),
                Forms\Components\FileUpload::make('home.hero.video')->label('Hintergrundvideo (optional, MP4)')
                    ->disk('public')->directory('home')->visibility('public')->acceptedFileTypes(['video/mp4'])->maxSize(51200)
                    ->helperText('Wird erst nach dem Laden der Seite nachgeladen (LCP bleibt der Text).'),
            ]),
            Forms\Components\Section::make('Kennzahlen')->collapsible()->collapsed()->schema([
                Forms\Components\Repeater::make('home.stats')->hiddenLabel()->columns(3)->maxItems(4)->reorderable()
                    ->schema([
                        Forms\Components\TextInput::make('value')->label('Zahl')->numeric()->required(),
                        Forms\Components\TextInput::make('suffix')->label('Zusatz')->maxLength(5)->placeholder('+'),
                        Forms\Components\TextInput::make('label')->label('Beschriftung')->required()->maxLength(60),
                    ])->addActionLabel('Kennzahl hinzufügen'),
            ]),
            Forms\Components\Section::make('Versprechen & USPs (Interest)')->collapsible()->collapsed()->schema([
                $text('home.promise_title', 'Überschrift', 80),
                Forms\Components\Textarea::make('home.promise_text')->label('Text')->rows(3)->maxLength(500),
                Forms\Components\Repeater::make('home.usps')->label('Vorteile')->columns(2)->maxItems(6)->reorderable()->collapsible()
                    ->itemLabel(fn (array $state) => $state['title'] ?? null)
                    ->schema([
                        Forms\Components\Select::make('icon')->label('Symbol')->options(self::ICONS)->required()->default('shield'),
                        Forms\Components\TextInput::make('title')->label('Titel')->required()->maxLength(80),
                        Forms\Components\Textarea::make('text')->label('Text')->required()->rows(2)->maxLength(300)->columnSpanFull(),
                    ])->addActionLabel('Vorteil hinzufügen'),
            ]),
            Forms\Components\Section::make('So einfach geht’s (Schritte)')->collapsible()->collapsed()->schema([
                $text('home.steps_title', 'Überschrift', 80),
                Forms\Components\Repeater::make('home.steps')->label('Schritte')->columns(2)->minItems(1)->maxItems(4)->reorderable()
                    ->schema([
                        Forms\Components\TextInput::make('title')->label('Titel')->required()->maxLength(60),
                        Forms\Components\Textarea::make('text')->label('Text')->required()->rows(2)->maxLength(300),
                    ])->addActionLabel('Schritt hinzufügen'),
            ]),
            Forms\Components\Section::make('Leadmagnet (Ratgeber)')->collapsible()->collapsed()->schema([
                $text('home.lead.title', 'Titel', 120),
                Forms\Components\Textarea::make('home.lead.text')->label('Text')->rows(2)->maxLength(300),
                Forms\Components\FileUpload::make('home.lead.file')->label('PDF-Datei')
                    ->disk('public')->directory('downloads')->acceptedFileTypes(['application/pdf'])->maxSize(20480)
                    ->helperText('Wird nach bestätigter Newsletter-Anmeldung zum Download angeboten.'),
            ]),
            Forms\Components\Section::make('Abschluss-CTA (Action)')->collapsible()->collapsed()->columns(2)->schema([
                $text('home.cta.headline', 'Überschrift', 120)->columnSpanFull(),
                Forms\Components\Textarea::make('home.cta.text')->label('Text')->rows(2)->maxLength(300)->columnSpanFull(),
                $text('home.cta.label', 'Button-Text', 40),
                $text('home.cta.url', 'Button-Link', 255),
            ]),
        ]);
    }

    private function paymentTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Zahlarten')->icon('heroicon-o-credit-card')->schema([
            Forms\Components\Section::make('Zahlarten an/aus')
                ->description('Online-Zahlarten werden nur aktiv, wenn die Zugangsdaten in der .env hinterlegt sind. Welche Zahlart für welche Ware erlaubt ist, steuern die Rechtsklassen.')
                ->columns(2)
                ->schema(collect(PaymentMethod::cases())->map(fn (PaymentMethod $m) => Forms\Components\Toggle::make("payment_methods.{$m->value}")
                    ->label($m->label())
                    ->helperText($m->isConfigured() ? $m->description() : 'Zugangsdaten fehlen – bleibt deaktiviert, bis sie in der .env stehen.'))
                    ->all()),
        ]);
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Speichern')->submit('save')->keyBindings(['mod+s'])];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $home = $state['home'] ?? [];
        $home['hero']['headline'] = implode('|', array_map('trim', array_values($home['hero']['headline_lines'] ?? [])));
        unset($home['hero']['headline_lines']);
        foreach (self::HOME_LISTS as $list) {
            $home[$list] = array_values($home[$list] ?? []);
        }
        foreach ($home['stats'] as $i => $stat) {
            $home['stats'][$i]['value'] = (int) $stat['value'];
            $home['stats'][$i]['suffix'] = (string) ($stat['suffix'] ?? '');
        }

        $values = [
            'home' => $home,
            'product_why' => array_values(array_filter(array_map('trim', $state['product_why'] ?? []))),
            'community' => array_map(fn ($v) => filled($v) ? trim($v) : null, Arr::only($state['community'] ?? [], array_keys(self::COMMUNITY))),
            'promo_bar' => self::sanitizePromo($state['promo_bar'] ?? null),
            'seo' => [
                'home_title' => filled($state['seo']['home_title'] ?? null) ? trim($state['seo']['home_title']) : null,
                'home_description' => filled($state['seo']['home_description'] ?? null) ? trim($state['seo']['home_description']) : null,
            ],
            'elite' => ['price' => (int) ($state['elite_price'] ?? 9900)],
            'payment_methods' => collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $m) => [$m->value => (bool) ($state['payment_methods'][$m->value] ?? false)])->all(),
        ];

        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        });

        self::flushCaches();

        Notification::make()->success()->title('Einstellungen gespeichert')->body('Die Änderungen sind sofort im Shop sichtbar.')->send();

        $this->form->fill(self::loadState(app(SettingsStore::class)));
    }

    /** Einstellungs-Cache, Ganzseiten-Cache und Startseiten-Daten verwerfen. */
    public static function flushCaches(): void
    {
        app(SettingsStore::class)->forget(); // leert auch PageCache::flush()
        Cache::forget('home.v2');
    }

    /** Die Promo-Leiste wird roh ausgegeben: nur wenige Tags, keine Event-Handler oder javascript:-Links. */
    public static function sanitizePromo(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }
        $html = strip_tags(trim($html), '<a><strong><em><b><i><br>');
        $html = preg_replace('/\s+(on\w+|style)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/href\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^"\'\s>]*\1/i', 'href="#"', $html);

        return $html;
    }
}
