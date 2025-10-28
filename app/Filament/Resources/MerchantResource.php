<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantResource\Pages;
use App\Filament\Resources\MerchantResource\RelationManagers;
use App\Models\Merchant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Spatie\Permission\Models\Role;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\View;


class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Merchant';

    
    public static function form(Form $form): Form
{
    return $form->schema([
        Select::make('catagory_id')
            ->relationship('merchantcategory', 'cat_name')
            ->searchable()
            ->preload(),

        TextInput::make('name')->required()->maxLength(255),

        TextInput::make('mobile')->required()->tel()->maxLength(10),

        TextInput::make('address')->required()->maxLength(255),

        Forms\Components\FileUpload::make('thumbnail')->label('Thumbnail')->required(),

        Forms\Components\Select::make('status')
            ->label('Merchant Status')
            ->options([1 => 'Active', 0 => 'Inactive'])
            ->required(),

        Forms\Components\Select::make('merchant_type')
            ->label('Merchant Type')
            ->options([
                'associative' => 'Associative',
                'default'     => 'Default',
                'none'        => 'None',
            ])
            ->required()
            ->default('none')
            ->native(false),

        TextInput::make('lat')->label('Latitude')->reactive()->required(),
        TextInput::make('lang')->label('Longitude')->reactive()->required(),

        // Razorpay IDs (from step 1)
        TextInput::make('razorpay_contact_id')
            ->label('Razorpay Contact ID')
            ->placeholder('cont_xxxxxxxxxxxxx')
            ->required(fn (string $operation): bool => $operation === 'create')
            ->rule('regex:/^cont_[A-Za-z0-9]+$/')
            ->unique(ignoreRecord: true)
            ->maxLength(191),

        TextInput::make('razorpay_fund_account_id')
            ->label('Razorpay Fund Account ID')
            ->placeholder('fa_xxxxxxxxxxxxx')
            ->required(fn (string $operation): bool => $operation === 'create')
            ->rule('regex:/^fa_[A-Za-z0-9]+$/')
            ->unique(ignoreRecord: true)
            ->maxLength(191),

        View::make('components.location-picker')
            ->columnSpanFull()
            ->viewData(fn (? \App\Models\Merchant $record) => [
                'lat'  => $record?->lat  ?? 19.1545,
                'lang' => $record?->lang ?? 77.3210,
            ]),
    ]);
}


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                  
                    '1' => 'success',
                    '0' => 'danger',
                })
                ->formatStateUsing(function ($state) {
                    return $state === 1 ? 'Active' : 'Inactive';
                })
                ->sortable(),
                    
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('Thumbnail')
                    ->circular(), 
                    
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('razorpay_contact_id')
                    ->label('RZP Contact')
                    ->toggleable()
                    ->copyable()
                    ->limit(18),

                Tables\Columns\TextColumn::make('razorpay_fund_account_id')
                    ->label('RZP Fund Acc')
                    ->toggleable()
                    ->copyable()
                    ->limit(18),
                Tables\Columns\TextColumn::make('merchant_type')
                    ->label('Merchant Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'associative' => 'info',
                        'default' => 'success',
                        'none' => 'secondary',
                    })
                    ->sortable(),
                
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
            'index' => Pages\ListMerchants::route('/'),
            'create' => Pages\CreateMerchant::route('/create'),
            'edit' => Pages\EditMerchant::route('/{record}/edit'),
        ];
    }
}
