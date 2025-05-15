<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletReportResource\Pages;
use App\Filament\Resources\WalletReportResource\RelationManagers;
use App\Models\WalletReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WalletReportResource extends Resource
{
    protected static ?string $model = WalletReport::class;
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    } 

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uid')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('status')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('amt')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amt')
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
            'index' => Pages\ListWalletReports::route('/'),
            'create' => Pages\CreateWalletReport::route('/create'),
            'edit' => Pages\EditWalletReport::route('/{record}/edit'),
        ];
    }
}
