<?php
// app/Filament/Resources/MerchantProductResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantProductResource\Pages;
use App\Models\MerchantProduct;
use App\Models\Merchant;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MerchantProductResource extends Resource
{
    protected static ?string $model = MerchantProduct::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?string $modelLabel = 'Merchant Product';
    protected static ?string $pluralModelLabel = 'Merchant Products';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(12)->schema([
                Forms\Components\Select::make('merchant_id')
                    ->label('Merchant')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return Merchant::query()
                            ->where('name', 'like', "%{$search}%")
                            ->orderBy('name')
                            ->limit(50)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value) => optional(Merchant::find($value))->name)
                    ->required()
                    ->columnSpan(6),

                Forms\Components\Select::make('product_id')
                    ->label('Product')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return Product::query()
                            ->where('name', 'like', "%{$search}%")
                            ->orderBy('name')
                            ->limit(50)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value) => optional(Product::find($value))->name)
                    ->required()
                    ->columnSpan(6),

                Forms\Components\TextInput::make('price')
                    ->label('Original Price')
                    ->numeric()->prefix('₹')->minValue(0)->required()->columnSpan(4),

                Forms\Components\TextInput::make('discount')
                    ->label('Discounted Price')
                    ->helperText('Leave empty to sell at original price.')
                    ->numeric()->prefix('₹')->minValue(0)->rule('lte:price')->nullable()->columnSpan(4),

                Forms\Components\TextInput::make('stock')
                    ->numeric()->minValue(0)->required()->columnSpan(4),

                Forms\Components\Textarea::make('description')
                    ->rows(3)->maxLength(2000)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('merchant.name')
                    ->label('Merchant')->sortable()->searchable(),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')->sortable()->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Original')->money('INR', true)->sortable(),

                Tables\Columns\TextColumn::make('discount')
                    ->label('Discounted')->money('INR', true)->toggleable()->sortable(),

                Tables\Columns\TextColumn::make('final_price')
                    ->label('Final')->money('INR', true)
                    ->sortable(fn (Builder $q, string $dir) => $q->orderBy('price', $dir)),

                Tables\Columns\TextColumn::make('stock')->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->since()->dateTimeTooltip()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->searchDebounce('600ms')
            ->filters([
                Tables\Filters\TernaryFilter::make('in_stock')->label('In stock')->queries(
                    true: fn (Builder $q) => $q->where('stock', '>', 0),
                    false: fn (Builder $q) => $q->where('stock', '=', 0),
                    blank: fn (Builder $q) => $q
                ),
                Tables\Filters\SelectFilter::make('merchant_id')->label('Merchant')->relationship('merchant', 'name'),
                Tables\Filters\SelectFilter::make('product_id')->label('Product')->relationship('product', 'name'),
                Tables\Filters\TrashedFilter::make(), // works with SoftDeletes; default view still excludes trashed
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Eager-load only what the table needs, and select lean columns
        return parent::getEloquentQuery()
            ->with(['merchant:id,name', 'product:id,name'])
            ->select(['id','merchant_id','product_id','price','discount','stock','updated_at']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMerchantProducts::route('/'),
            'create' => Pages\CreateMerchantProduct::route('/create'),
            'edit'   => Pages\EditMerchantProduct::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        // Keep global search light; avoid large TEXT columns like description
        return ['merchant.name', 'product.name'];
    }
}
