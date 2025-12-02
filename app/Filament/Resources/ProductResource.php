<?php

namespace App\Filament\Resources;

use App\Filament\Components\FallbackFileUpload;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Support\StorageFallback;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-s-cube';
    protected static ?string $navigationGroup = 'Catalog';
    
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
               
                
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->columnSpanFull(),
                Select::make('cat_id')
                    ->relationship('productcategory','title')
                    ->searchable()
                    ->preload(),
                Select::make('subcat_id')
                    ->relationship('subcategory','title')
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('Rs'),
                Forms\Components\TextInput::make('discount_price')
                    ->required()
                    ->numeric()
                    ->prefix('Rs'),
                Forms\Components\Select::make('status')
                    ->label('Status') // Optional: To set a custom label
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ])
                    ->required(),

                FallbackFileUpload::make('thumbnail')
                    ->label('Thumbnail')
                    ->image()
                    ->disk('s3')
                    ->imagePreviewHeight('150')
                    ->directory('thumbnails')
                    ->visibility('public'),
            
                Forms\Components\TextInput::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('productCategory.title')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subcategory.title')
                    ->label('Sub Category')
                    ->sortable(),
               
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    // ->numeric()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                      
                        '1' => 'success',
                        '0' => 'danger',
                    })
                    ->formatStateUsing(function ($state) {
                        return $state === 1 ? 'Published' : 'Unpublished';
                    })
                    ->sortable(),

                    Tables\Columns\ImageColumn::make('thumbnail')
                        ->label('Thumbnail')
                        ->getStateUsing(fn ($record) => StorageFallback::url($record->thumbnail))
                        ->circular(), // Optional: Makes image circular

                    Tables\Columns\TextColumn::make('description')
                    ->label('description'),
               
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
