<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DropPointResource\Pages;
use App\Filament\Resources\DropPointResource\RelationManagers;
use App\Models\DropPoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DropPointResource extends Resource
{
    protected static ?string $model = DropPoint::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    } 

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('uid')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('drop_address')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('drop_lat')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('drop_lng')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('drop_name')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('drop_mobile')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('status')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('photos')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('uid')
                    ->numeric()
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
            'index' => Pages\ListDropPoints::route('/'),
            'create' => Pages\CreateDropPoint::route('/create'),
            'edit' => Pages\EditDropPoint::route('/{record}/edit'),
        ];
    }
}
