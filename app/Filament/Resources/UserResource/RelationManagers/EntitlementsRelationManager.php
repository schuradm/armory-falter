<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\EntitlementType;
use App\Models\CustomerEntitlement;
use App\Services\AuditLogger;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Erwerbsberechtigungen des Kunden. Angelegt werden sie über die Dokumentenprüfung. */
class EntitlementsRelationManager extends RelationManager
{
    protected static string $relationship = 'entitlements';

    protected static ?string $title = 'Erwerbsberechtigungen';

    protected static ?string $modelLabel = 'Berechtigung';

    protected static ?string $pluralModelLabel = 'Berechtigungen';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['caliber', 'document']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('Art')->wrap(),
                Tables\Columns\TextColumn::make('weapon_kind')->label('Waffenart')->placeholder('—'),
                Tables\Columns\TextColumn::make('caliber.name')->label('Kaliber')->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity')->label('Verbraucht')
                    ->getStateUsing(fn (CustomerEntitlement $record) => $record->quantity_total === null ? 'unbegrenzt' : "{$record->quantity_used} / {$record->quantity_total}"),
                Tables\Columns\TextColumn::make('valid_until')->label('Gültig bis')->date('d.m.Y')->placeholder('unbefristet'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => ['active' => 'aktiv', 'consumed' => 'verbraucht', 'expired' => 'abgelaufen', 'revoked' => 'widerrufen'][$state] ?? $state)
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Art')->options(EntitlementType::class),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('revoke')->label('Widerrufen')->icon('heroicon-o-no-symbol')->color('danger')
                    ->visible(fn (CustomerEntitlement $record) => $record->status === 'active' && auth()->user()?->can('compliance.release'))
                    ->requiresConfirmation()
                    ->action(function (CustomerEntitlement $record) {
                        $record->update(['status' => 'revoked']);
                        app(AuditLogger::class)->log('entitlement.revoked', $record);
                    }),
            ])
            ->bulkActions([]);
    }
}
