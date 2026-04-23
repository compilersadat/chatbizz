<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentListResource\Pages;
use App\Filament\Resources\PaymentListResource\RelationManagers;
use App\Models\PaymentList;
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

class PaymentListResource extends Resource
{
    protected static ?string $model = PaymentList::class;
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    } 
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('title')
                    ->label('Payment Gateway Name')
                    ->required()
                    ->columnSpanFull(),
                // Forms\Components\Textarea::make('img')
                //     ->required()
                //     ->columnSpanFull(),
                FileUpload::make('img')
                    ->label('Payment Gateway Image')
                    ->disk('s3')
                    ->directory('payment-list')
                    ->required()
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = 'payment-list/' . $filename;

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
                Forms\Components\Textarea::make('attributes')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Payment Gateway Status')
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ])
                    ->required()->columnSpanFull(),
                Forms\Components\Textarea::make('subtitle')
                    ->label('Payment Gateway subtitle')
                    ->columnSpanFull(),
                Forms\Components\Select::make('p_show')
                    ->label('Show on Wallet')
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ])
                    ->required()->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Sr No.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Payment Gateway Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->label('Payment Gateway subtitle')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Payment Gateway Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Published' : 'Unpublished')
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('p_show')
                    ->label('Show on Wallet')
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
            'index' => Pages\ListPaymentLists::route('/'),
            'create' => Pages\CreatePaymentList::route('/create'),
            'edit' => Pages\EditPaymentList::route('/{record}/edit'),
        ];
    }
}
