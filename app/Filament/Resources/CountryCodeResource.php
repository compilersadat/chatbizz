<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CountryCodeResource\Pages;
use App\Filament\Resources\CountryCodeResource\RelationManagers;
use App\Models\CountryCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CountryCodeResource extends Resource
{
    protected static ?string $model = CountryCode::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    } 

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textinput::make('ccode')
                    ->label('Country Code')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Coupon Status')
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('ccode')
                    ->sortable(),


                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Published' : 'Unpublished')
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                    })
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
            'index' => Pages\ListCountryCodes::route('/'),
            'create' => Pages\CreateCountryCode::route('/create'),
            'edit' => Pages\EditCountryCode::route('/{record}/edit'),
        ];
    }
}
