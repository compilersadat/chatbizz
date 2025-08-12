<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantProductResource\Pages;
use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SubCategory;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MerchantProductResource extends Resource
{
    protected static ?string $model = MerchantProduct::class;

    protected static ?string $navigationGroup = 'Catalog';
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Merchant Products';
    protected static ?string $pluralLabel = 'Merchant Products';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('merchant_id')
                ->label('Merchant')
                ->searchable()
                ->required()
                ->options(fn () => Merchant::query()->orderBy('name')->pluck('name', 'id')->all())
                ->native(false)
                ->preload(),

            // Catalog-style product picker with category filters
            Forms\Components\Group::make()
    ->columns(3)
    ->schema([
        Forms\Components\Select::make('cat_id')
            ->label('Category')
            ->options(fn () => \App\Models\ProductCategory::query()->orderBy('title')->pluck('title', 'id'))
            ->reactive()
            ->afterStateUpdated(fn (Forms\Set $set) => $set('subcat_id', null)),

        Forms\Components\Select::make('subcat_id')
            ->label('Subcategory')
            ->options(function (Forms\Get $get) {
                $catId = $get('cat_id');
                $q = \App\Models\SubCategory::query()->orderBy('title');
                if ($catId) $q->where('cat_id', $catId);
                return $q->pluck('title', 'id');
            })
            ->reactive(),

        Forms\Components\Select::make('product_id')
            ->label('Product')
            ->required()
            ->searchable()
            ->preload()
            ->options(function (Forms\Get $get) {
                $q = \App\Models\Product::query()
                    ->with(['productcategory', 'subcategory'])
                    ->orderBy('title');

                if ($get('cat_id'))    $q->where('cat_id', $get('cat_id'));
                if ($get('subcat_id')) $q->where('subcat_id', $get('subcat_id'));

                return $q->get()->mapWithKeys(function ($product) {
                    $cat = optional($product->productcategory)->title;
                    $sub = optional($product->subcategory)->title;
                    $label = $product->title . ($cat ? " — {$cat}" : '') . ($sub ? " / {$sub}" : '');
                    return [$product->id => $label];
                })->toArray();
            })
            ->native(false),
    ]),


            Forms\Components\TextInput::make('price')
                ->numeric()->step('0.01')->required(),

            Forms\Components\TextInput::make('discount')
                ->numeric()->step('0.01')->default(0),

            Forms\Components\TextInput::make('stock')
                ->numeric()->minValue(0)->default(0),

            Forms\Components\Textarea::make('description')
                ->rows(3)->maxLength(1000),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['merchant', 'product.productcategory', 'product.subcategory'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->sortable()->searchable(),

                Tables\Columns\ImageColumn::make('product.thumbnail')
                    ->label('Thumb')
                    ->getStateUsing(fn (MerchantProduct $record) => $record->product?->thumbnail)
                    ->square()
                    ->height(36)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('product.title')
                    ->label('Product')
                    ->sortable()->searchable(),

                Tables\Columns\TextColumn::make('product.productcategory.title')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('product.subcategory.title')
                    ->label('Subcategory')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('INR', true)->sortable(),

                Tables\Columns\TextColumn::make('discount')
                    ->label('Discount')->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('merchant_id')
                    ->label('Merchant')
                    ->options(fn () => Merchant::orderBy('name')->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('cat_id')
                    ->label('Category')
                    ->options(fn () => ProductCategory::orderBy('title')->pluck('title', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('product', fn ($q) => $q->where('cat_id', $data['value']));
                        }
                    }),

                Tables\Filters\SelectFilter::make('subcat_id')
                    ->label('Subcategory')
                    ->options(fn () => SubCategory::orderBy('title')->pluck('title', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('product', fn ($q) => $q->where('subcat_id', $data['value']));
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->headerActions([
                // BULK ADD (drag & drop)
                Tables\Actions\Action::make('bulkAdd')
                    ->label('Bulk Add')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Bulk Add Products to a Merchant')
                    ->modalSubmitActionLabel('Add Selected')
                    ->form([
                        Forms\Components\Select::make('merchant_id')
                            ->label('Merchant')
                            ->searchable()
                            ->required()
                            ->options(fn () => Merchant::orderBy('name')->pluck('name', 'id'))
                            ->native(false),
                            Forms\Components\Repeater::make('items')
                            ->label('Select Products')
                            ->reorderable() // drag & drop
                            ->defaultItems(1)
                            ->columns(6)
                            ->schema([
                                Forms\Components\Select::make('cat_id')
                                    ->label('Category')
                                    ->options(fn () => \App\Models\ProductCategory::orderBy('title')->pluck('title', 'id'))
                                    ->reactive()
                                    ->afterStateUpdated(function (Forms\Set $set) {
                                        $set('subcat_id', null);
                                        $set('product_id', null);
                                    })
                                    ->columnSpan(2),
                        
                                Forms\Components\Select::make('subcat_id')
                                    ->label('Subcategory')
                                    ->options(function (Forms\Get $get) {
                                        $catId = $get('cat_id');
                                        $q = \App\Models\SubCategory::query()->orderBy('title');
                                        if ($catId) $q->where('cat_id', $catId);
                                        return $q->pluck('title', 'id');
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('product_id', null))
                                    ->columnSpan(2),
                        
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(function (Forms\Get $get) {
                                        $catId = $get('cat_id');
                                        $subId = $get('subcat_id');
                                        $q = \App\Models\Product::query()
                                            ->with(['productcategory', 'subcategory'])
                                            ->orderBy('title');
                        
                                        if ($catId) $q->where('cat_id', $catId);
                                        if ($subId) $q->where('subcat_id', $subId);
                        
                                        return $q->get()->mapWithKeys(function ($product) {
                                            $cat = optional($product->productcategory)->title;
                                            $sub = optional($product->subcategory)->title;
                                            $label = $product->title . ($cat ? " — {$cat}" : '') . ($sub ? " / {$sub}" : '');
                                            return [$product->id => $label];
                                        })->toArray();
                                    })
                                    ->native(false)
                                    ->columnSpan(2),
                        
                                Forms\Components\TextInput::make('price')
                                    ->numeric()->step('0.01')->required()
                                    ->columnSpan(2),
                        
                                Forms\Components\TextInput::make('discount')
                                    ->numeric()->step('0.01')->default(0)
                                    ->columnSpan(2),
                        
                                Forms\Components\TextInput::make('stock')
                                    ->numeric()->minValue(0)->default(0)
                                    ->columnSpan(2),
                        
                                Forms\Components\Textarea::make('description')
                                    ->rows(2)->maxLength(500)
                                    ->columnSpan(6),
                            ])
                            ->addActionLabel('Add another product'),
                        
                    ])
                    ->action(function (array $data) {
                        /** @var int $merchantId */
                        $merchantId = $data['merchant_id'];
                        /** @var array<int, array> $items */
                        $items = $data['items'] ?? [];

                        // Upsert each selected product for the merchant
                        collect($items)->each(function ($row) use ($merchantId) {
                            if (empty($row['product_id'])) {
                                return;
                            }
                            MerchantProduct::updateOrCreate(
                                [
                                    'merchant_id' => $merchantId,
                                    'product_id'  => $row['product_id'],
                                ],
                                [
                                    'price'       => $row['price'] ?? 0,
                                    'discount'    => $row['discount'] ?? 0,
                                    'stock'       => $row['stock'] ?? 0,
                                    'description' => $row['description'] ?? null,
                                ],
                            );
                        });
                    })
                    ->successNotificationTitle('Products added/updated'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMerchantProducts::class,
            'create' => Pages\CreateMerchantProduct::class,
            'edit'   => Pages\EditMerchantProduct::class,
        ];
    }
}
