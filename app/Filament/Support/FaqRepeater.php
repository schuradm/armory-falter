<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

/** Repeater für FAQ-Listen, die als JSON-Spalte `faqs` gespeichert werden: [{question, answer}, …]. */
final class FaqRepeater
{
    public static function make(string $name = 'faqs'): Repeater
    {
        return Repeater::make($name)
            ->label('Häufige Fragen (FAQ)')
            ->helperText('Erscheinen auf der Seite und als FAQ-Rich-Snippet (JSON-LD).')
            ->schema([
                TextInput::make('question')->label('Frage')->required()->maxLength(255),
                Textarea::make('answer')->label('Antwort')->required()->rows(3),
            ])
            ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
            ->addActionLabel('Frage hinzufügen')
            ->reorderable()
            ->collapsible()
            ->defaultItems(0)
            ->columnSpanFull();
    }
}
