<?php
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
                        return \App\Models\Merchant::associative()
                            ->when($search !== '', fn ($q) =>
                                $q->where('name', 'like', "%{$search}%")
                            )
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
                            ->where('title', 'like', "%{$search}%")
                            ->orderBy('title')
                            ->limit(50)
                            ->pluck('title', 'id')
                            ->toArray();
                    })
                    ->getOptionLabelUsing(fn ($value) => optional(Product::find($value))->title)
                    ->required()
                    ->columnSpan(6),

                Forms\Components\TextInput::make('price')
                    ->label('Original Price')
                    ->numeric()->prefix('₹')->minValue(0)->required()->columnSpan(4),

                Forms\Components\TextInput::make('discount')
                    ->label('Discounted Price')
                    ->helperText('Leave empty to sell at original price.')
                    ->numeric()
                    ->prefix('₹')
                    ->minValue(0)
                    ->rule('lte:data.price') // price input lives under the same data.* scope
                    ->columnSpan(4),

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
                ->label('Merchant')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('product.name')
                ->label('Product')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('price')
                ->label('Original')
                ->money('INR', true)
                ->sortable(),

            Tables\Columns\TextColumn::make('discount')
                ->label('Discounted')
                ->money('INR', true)
                ->sortable(),

            Tables\Columns\TextColumn::make('final_price')
                ->label('Final')
                ->money('INR', true)
                ->sortable(),

            Tables\Columns\TextColumn::make('stock')
                ->sortable(),

            Tables\Columns\TextColumn::make('updated_at')
                ->since()
                ->dateTimeTooltip(),
        ])
        ->defaultSort('id', 'desc')
        ->filters([]) // keep it minimal; add later if needed
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
}

    


    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListMerchantProducts::route('/'),
            'create' => Pages\CreateMerchantProduct::route('/create'),
            'edit'   => Pages\EditMerchantProduct::route('/{record}/edit'),
        ];
    }
}
