<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantCatagoryResource\Pages;
use App\Filament\Resources\MerchantCatagoryResource\RelationManagers;
use App\Support\StorageFallback;
use App\Models\MerchantCatagory;
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

class MerchantCatagoryResource extends Resource
{
    protected static ?string $model = MerchantCatagory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('cat_name')
                    ->label('Category Name')
                    ->required()
                    ->columnSpanFull(),
                    Forms\Components\Select::make('cat_status')
                    ->label('Category Status')
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ])
                    ->required(),
                    FileUpload::make('cat_img')->label('Category Image')
                    ->disk('s3')
                    ->directory('merchant-categories')
                    ->required()
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = 'merchant-categories/' . $filename;

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
            Tables\Columns\TextColumn::make('cat_name')
                ->label('Category Name')
                ->sortable(),
            Tables\Columns\TextColumn::make('cat_status')
                ->label('Category Status')
                ->badge()
                ->formatStateUsing(fn ($state) => $state === 1 ? 'Published' : 'Unpublished')
                ->color(fn (string $state): string => match ($state) {
                    '1' => 'success',
                    '0' => 'danger',
                })
                ->sortable(),
             Tables\Columns\ImageColumn::make('cat_img')
                ->label('Image')
                ->getStateUsing(fn ($record) => StorageFallback::url($record->cat_img))
                ->circular(), 
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
            'index' => Pages\ListMerchantCatagories::route('/'),
            'create' => Pages\CreateMerchantCatagory::route('/create'),
            'edit' => Pages\EditMerchantCatagory::route('/{record}/edit'),
        ];
    }
}
