<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms\Components\TextInput;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/** Revisionssicheres Protokoll (Hash-Kette): nur lesen, niemals ändern oder löschen. */
class AuditLogResource extends Resource
{
    use RequiresPermission;

    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $modelLabel = 'Protokolleintrag';

    protected static ?string $pluralModelLabel = 'Audit-Log';

    protected static ?string $navigationLabel = 'Audit-Log';

    protected static ?int $navigationSort = 90;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Eintrag')->columns(3)->schema([
                Infolists\Components\TextEntry::make('created_at')->label('Zeitpunkt')->dateTime('d.m.Y H:i:s'),
                Infolists\Components\TextEntry::make('action')->label('Aktion')->badge(),
                Infolists\Components\TextEntry::make('actor_type')->label('Auslöser-Typ'),
                Infolists\Components\TextEntry::make('actor.email')->label('Benutzer')->placeholder('System'),
                Infolists\Components\TextEntry::make('subject_type')->label('Objekt')->placeholder('–'),
                Infolists\Components\TextEntry::make('subject_id')->label('Objekt-ID')->placeholder('–'),
                Infolists\Components\TextEntry::make('ip_address')->label('IP-Adresse')->placeholder('–'),
                Infolists\Components\TextEntry::make('user_agent')->label('User-Agent')->placeholder('–')->columnSpan(2),
            ]),
            Infolists\Components\Section::make('Änderungen')->columns(2)->schema([
                self::jsonEntry('old_values', 'Vorher'),
                self::jsonEntry('new_values', 'Nachher'),
            ]),
            Infolists\Components\Section::make('Hash-Kette')->collapsed()->schema([
                Infolists\Components\TextEntry::make('prev_hash')->label('Vorheriger Hash')->fontFamily('mono')->placeholder('–'),
                Infolists\Components\TextEntry::make('hash')->label('Hash')->fontFamily('mono'),
            ]),
        ]);
    }

    private static function jsonEntry(string $field, string $label): Infolists\Components\TextEntry
    {
        return Infolists\Components\TextEntry::make($field)->label($label)->placeholder('–')
            ->getStateUsing(fn (AuditLog $r) => $r->{$field} ? json_encode($r->{$field}, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null)
            ->formatStateUsing(fn (string $state) => new HtmlString('<pre class="whitespace-pre-wrap text-xs">'.e($state).'</pre>'));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('actor:id,email,first_name,last_name'))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Zeitpunkt')->dateTime('d.m.Y H:i:s')->sortable(),
                Tables\Columns\TextColumn::make('action')->label('Aktion')->badge()->searchable(),
                Tables\Columns\TextColumn::make('actor.email')->label('Benutzer')->placeholder('System'),
                Tables\Columns\TextColumn::make('actor_type')->label('Typ')->toggleable(),
                Tables\Columns\TextColumn::make('subject_type')->label('Objekt')
                    ->formatStateUsing(fn (AuditLog $r) => $r->subject_type ? "{$r->subject_type} #{$r->subject_id}" : '–'),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('actor_type')->label('Typ')->options(['admin' => 'Mitarbeiter', 'user' => 'Kunde', 'system' => 'System']),
                Tables\Filters\Filter::make('subject')->label('Objekt')
                    ->form([
                        TextInput::make('subject_type')->label('Objekt-Typ (z. B. order)'),
                        TextInput::make('subject_id')->label('Objekt-ID')->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['subject_type'] ?? null, fn ($query, $v) => $query->where('subject_type', $v))
                        ->when($data['subject_id'] ?? null, fn ($query, $v) => $query->where('subject_id', $v))),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
