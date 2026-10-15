<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Str;

/** Wiederkehrende Formularbausteine für das Backend (Slug, Euro-Beträge in Cent, SEO, Inhalte). */
class FormFields
{
    /** Titel-Feld, das beim Anlegen (oder solange der Slug leer ist) den Slug befüllt. */
    public static function title(string $name = 'title', string $label = 'Titel', string $slugField = 'slug', int $max = 200): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->required()
            ->maxLength($max)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) use ($slugField) {
                if ($operation === 'create' || blank($get($slugField))) {
                    $set($slugField, Str::slug((string) $state));
                }
            });
    }

    public static function slug(string $name = 'slug', int $max = 220, ?string $prefix = null): TextInput
    {
        return TextInput::make($name)
            ->label('URL-Kürzel (Slug)')
            ->required()
            ->maxLength($max)
            ->unique(ignoreRecord: true)
            ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
            ->validationMessages(['regex' => 'Nur Kleinbuchstaben, Ziffern und Bindestriche.'])
            ->prefix($prefix)
            ->helperText('Wird aus dem Titel erzeugt. Nach Veröffentlichung nur mit Weiterleitung ändern.');
    }

    /** Betrag in Euro eingeben, in Cent speichern. */
    public static function euro(string $name, string $label, bool $required = false): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->prefix('€')
            ->inputMode('decimal')
            ->required($required)
            ->rule('regex:/^\d{1,7}([.,]\d{1,2})?$/')
            ->validationMessages(['regex' => 'Bitte einen Betrag wie 12,50 eingeben.'])
            ->formatStateUsing(fn ($state) => $state === null || $state === '' ? null : number_format(((int) $state) / 100, 2, ',', ''))
            ->dehydrateStateUsing(fn ($state) => self::toCents($state));
    }

    public static function toCents(mixed $state): ?int
    {
        if ($state === null || $state === '') {
            return null;
        }

        // Eingabe ist per Regel auf „1234“, „1234,5“ oder „1234.50“ beschränkt (ohne Tausenderpunkt).
        return (int) round(((float) str_replace(',', '.', (string) $state)) * 100);
    }

    /** HTML-Inhalt (Frontend gibt den Text als HTML in .prose-af aus). */
    public static function content(string $name = 'body', string $label = 'Inhalt', string $directory = 'content'): RichEditor
    {
        return RichEditor::make($name)
            ->label($label)
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory($directory)
            ->fileAttachmentsVisibility('public')
            ->toolbarButtons(['bold', 'italic', 'underline', 'strike', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'attachFiles', 'undo', 'redo'])
            ->columnSpanFull();
    }

    public static function image(string $name, string $label, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->visibility('public')
            ->imageEditor()
            ->maxSize(8192)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /** SEO-Felder über die Morph-Relation `seo` (Tabelle seo_meta). */
    public static function seoSection(): Section
    {
        return Section::make('SEO')
            ->description('Leer lassen = Titel und Auszug werden verwendet.')
            ->relationship('seo')
            ->collapsible()
            ->collapsed()
            ->columns(2)
            ->schema([
                TextInput::make('meta_title')->label('Meta-Titel')->maxLength(70)->helperText('Max. 70 Zeichen.'),
                TextInput::make('canonical_url')->label('Canonical-URL')->url()->maxLength(255),
                Textarea::make('meta_description')->label('Meta-Beschreibung')->maxLength(170)->rows(2)->helperText('Max. 170 Zeichen.')->columnSpanFull(),
                Select::make('robots')->label('Robots')->placeholder('Standard (index, follow)')->options([
                    'noindex, follow' => 'noindex, follow',
                    'noindex, nofollow' => 'noindex, nofollow',
                    'index, nofollow' => 'index, nofollow',
                ]),
                self::image('og_image_path', 'Social-Media-Bild (1200 × 630)', 'seo'),
            ]);
    }

    /** Status-Optionen für Inhalte. */
    public static function publishStatus(): array
    {
        return ['draft' => 'Entwurf', 'published' => 'Veröffentlicht'];
    }
}
