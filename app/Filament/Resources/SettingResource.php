<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Filament\Resources\SettingResource\RelationManagers;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\ImageEntry;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.resources.pages.settings';

    public static function form(Form $form): Form
    {
        return $form   
            ->schema([              //
                     Forms\Components\Textarea::make('webname')
                    ->required(),
                    Forms\Components\FileUpload::make('weblogo')
                    ->disk('s3')
                    ->visibility('public')
                    ->required(),
                Forms\Components\Textarea::make('timezone')
                    ->required(),
                Forms\Components\Textarea::make('currency')
                    ->required(),
                Forms\Components\TextInput::make('pdboy')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('one_key')
                    ->required(),
                Forms\Components\Textarea::make('one_hash')
                    ->required(),
                Forms\Components\Textarea::make('d_key')
                    ->required(),
                Forms\Components\Textarea::make('d_hash')
                    ->required(),
                Forms\Components\TextInput::make('scredit')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('rcredit')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('gkey')
                    ->required(),
                Forms\Components\TextInput::make('vehiid')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('couvid')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('kglimit')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('kgprice')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('semail')
                    ->required(),
                Forms\Components\Textarea::make('smobile')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pdboy')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scredit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rcredit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehiid')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('couvid')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kglimit')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kgprice')
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
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
            
    }

    public static function infolist(Infolist $infolist): Infolist
{
    return $infolist
        ->schema([

            // General Settings
            Fieldset::make('General Settings')
                ->schema([
                    TextEntry::make('webname')->label('Website Name'),
                    ImageEntry::make('weblogo')
                        ->label('Website Logo')
                        ->disk('s3'),
                    TextEntry::make('timezone')->label('Timezone'),
                    TextEntry::make('currency')->label('Currency'),
                ])
                ->columns(2),

            // API Keys and Hashes
            Fieldset::make('API Keys and Hashes')
                ->schema([
                    TextEntry::make('one_key')->label('One Key'),
                    TextEntry::make('one_hash')->label('One Hash')->copyable()
                    ->copyMessage('Copied!')
                    ->copyMessageDuration(1500) ->extraAttributes([
                        'class' => 'truncate', // Utility class for overflow
                        'style' => 'max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;',
                    ]),
                    TextEntry::make('d_key')->label('D Key'),
                    TextEntry::make('d_hash')->label('D Hash'),
                ])
                ->columns(2),

            // Credit Information
            Fieldset::make('Credit Information')
                ->schema([
                    TextEntry::make('scredit')->label('System Credit')->numeric(),
                    TextEntry::make('rcredit')->label('Referral Credit')->numeric(),
                ])
                ->columns(2),

            // Vehicle and Delivery Details
            Fieldset::make('Vehicle and Delivery Details')
                ->schema([
                    TextEntry::make('vehiid')->label('Vehicle ID')->numeric(),
                    TextEntry::make('couvid')->label('Country ID')->numeric(),
                    TextEntry::make('kglimit')->label('KG Limit')->numeric(),
                    TextEntry::make('kgprice')->label('KG Price')->numeric(),
                ])
                ->columns(2),

            // Contact Details
            Fieldset::make('Contact Details')
                ->schema([
                    TextEntry::make('semail')->label('Support Email'),
                    TextEntry::make('smobile')->label('Support Mobile'),
                ])
                ->columns(1),
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
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'view' => Pages\ViewSetting::route('/{record}'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}
