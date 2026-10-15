<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/** SEO-Felder über die morphOne-Relation `seo` (Trait App\Models\Concerns\HasSeo). */
final class SeoFields
{
    public static function make(): Group
    {
        return Group::make()
            ->relationship('seo')
            ->schema([
                TextInput::make('meta_title')->label('Meta-Titel')->maxLength(70)
                    ->helperText('Leer lassen = Name wird verwendet. Max. 70 Zeichen, ideal 50–60.'),
                Textarea::make('meta_description')->label('Meta-Beschreibung')->maxLength(170)->rows(3)
                    ->helperText('Max. 170 Zeichen, ideal 140–160. Mit Nutzen und Handlungsaufforderung.'),
                TextInput::make('canonical_url')->label('Canonical-URL')->url()->maxLength(255)
                    ->helperText('Nur setzen, wenn eine andere Seite die Hauptversion ist.'),
                Select::make('robots')->label('Robots')->placeholder('Standard (index, follow)')
                    ->options([
                        'index,follow' => 'index, follow',
                        'noindex,follow' => 'noindex, follow',
                        'noindex,nofollow' => 'noindex, nofollow',
                    ]),
                FileUpload::make('og_image_path')->label('Social-Media-Bild (1200 × 630)')
                    ->image()->disk('public')->directory('seo')->visibility('public')->maxSize(4096),
            ])
            ->columnSpanFull();
    }
}
