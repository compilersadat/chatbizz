<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantResource\Pages;
use App\Filament\Resources\MerchantResource\RelationManagers;
use App\Support\StorageFallback;
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
use Filament\Forms\Components\Section;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;

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

        FileUpload::make('thumbnail')
            ->label('Thumbnail')
            ->disk('s3')
            ->directory('thumbnails')
            ->image()
            ->required()
            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, $record): string {
                // Generate your own file name:
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $path = 'thumbnails/' . $filename;

                // Livewire temp files may live on S3, so stream instead of reading a local path
                $stream = $file->readStream();
                Storage::disk('s3')->put($path, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                Log::info('S3 upload result', [
                    'path'   => $path,
                    'exists' => Storage::disk('s3')->exists($path),
                ]);

                // This string will be saved into merchants.thumbnail
                return $path;
            }),

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

        Section::make('Payment Information')
            ->description('Store merchant payout details in the linked merchant account.')
            ->relationship('merchantAccount')
            ->schema([
                TextInput::make('account_holder_name')
                    ->label('Account Holder Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('bank_account_number')
                    ->label('Bank Account Number')
                    ->required()
                    ->maxLength(255),

                TextInput::make('ifsc_code')
                    ->label('IFSC Code')
                    ->required()
                    ->maxLength(255),

                TextInput::make('bank_name')
                    ->label('Bank Name')
                    ->maxLength(255),

                TextInput::make('bank_branch')
                    ->label('Bank Branch')
                    ->maxLength(255),

                Forms\Components\Select::make('verification_status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'failed' => 'Failed',
                    ])
                    ->default('pending'),

                Forms\Components\Textarea::make('kyc_notes')
                    ->label('KYC Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2),

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
                    ->getStateUsing(fn ($record) => StorageFallback::url($record->thumbnail))
                    ->circular(), 
                    
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('merchant_type', '!=', 'none');
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
