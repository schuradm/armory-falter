<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerDocumentResource\Pages;
use App\Filament\Resources\CustomerDocumentResource\RelationManagers;
use App\Models\CustomerDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerDocumentResource extends Resource
{
    protected static ?string $model = CustomerDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(30),
                Forms\Components\Textarea::make('document_number')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('document_number_bidx')
                    ->maxLength(64),
                Forms\Components\TextInput::make('issuing_authority')
                    ->maxLength(200),
                Forms\Components\DatePicker::make('issued_at'),
                Forms\Components\DatePicker::make('valid_until'),
                Forms\Components\TextInput::make('storage_path')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('original_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('mime_type')
                    ->required()
                    ->maxLength(60),
                Forms\Components\TextInput::make('file_size')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('sha256')
                    ->required()
                    ->maxLength(64),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(20)
                    ->default('uploaded'),
                Forms\Components\TextInput::make('precheck_result'),
                Forms\Components\TextInput::make('reviewed_by')
                    ->numeric(),
                Forms\Components\DateTimePicker::make('reviewed_at'),
                Forms\Components\TextInput::make('rejection_reason')
                    ->maxLength(500),
                Forms\Components\DatePicker::make('delete_after'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_number_bidx')
                    ->searchable(),
                Tables\Columns\TextColumn::make('issuing_authority')
                    ->searchable(),
                Tables\Columns\TextColumn::make('issued_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('storage_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('original_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mime_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('file_size')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sha256')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reviewed_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rejection_reason')
                    ->searchable(),
                Tables\Columns\TextColumn::make('delete_after')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerDocuments::route('/'),
            'create' => Pages\CreateCustomerDocument::route('/create'),
            'edit' => Pages\EditCustomerDocument::route('/{record}/edit'),
        ];
    }
}
