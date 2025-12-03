<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductCategoryResource\Pages;
use App\Filament\Resources\ProductCategoryResource\RelationManagers;
use App\Support\StorageFallback;
use App\Models\ProductCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Catalog';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status') // Optional: To set a custom label
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ])
                    ->required(),
                FileUpload::make('image')->label('Category Image')
                    ->disk('s3')
                    ->directory('product-categories')
                    ->required()
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = 'product-categories/' . $filename;

                        $stream = $file->readStream();
                        Storage::disk('s3')->put($path, $stream);
                        if (is_resource($stream)) {
                            fclose($stream);
                        }

                        Log::info('S3 upload result', [
                            'path' => $path,
                            'exists' => Storage::disk('s3')->exists($path),
                        ]);

                        return $path;
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
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
                    Tables\Columns\ImageColumn::make('image')
                    ->label('image')
                    ->getStateUsing(fn ($record) => StorageFallback::url($record->image))
                    ->circular(), // Optional: Makes image circular

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
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListProductCategories::route('/'),
            'create' => Pages\CreateProductCategory::route('/create'),
            'edit' => Pages\EditProductCategory::route('/{record}/edit'),
        ];
    }
}
