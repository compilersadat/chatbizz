<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiderResource\Pages;
use App\Filament\Resources\RiderResource\RelationManagers;
use App\Models\Rider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class RiderResource extends Resource
{
    protected static ?string $model = Rider::class;

    public static function getModelLabel(): string
    {
        return 'Delivery Boy'; // Singular name
    }

    public static function getPluralModelLabel(): string
    {
        return 'Delivery Boys'; // Plural name
    }

    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationGroup = 'Delivery';

    protected function getCreateFormActionLabel(): string
    {
        return 'Add';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Personal Information')
                    ->icon('heroicon-m-identification')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required(),
                        Forms\Components\TextInput::make('mobile')
                            ->required()
                            ->tel()
                            ->maxLength(10),
                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->email(),
                        Forms\Components\TextInput::make('password')
                        ->password()
                        ->nullable() // Allow null values during updates
                        ->required(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord) // Only required on create
                        ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null) // Hash password if provided
                        ->dehydrated(fn ($state) => !empty($state)), // Prevent overwriting with null
                    
                        Forms\Components\Select::make('status')
                            ->label('Account Status')
                            ->options([
                                1 => 'Published',
                                0 => 'Unpublished',
                            ])
                            ->required(),
                        
                        Forms\Components\Select::make('rstatus')
                            ->label('Profile Status')
                            ->options([
                                1 => 'Active',
                                0 => 'Inactive',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('rate')
                            ->required()
                            ->numeric(),
                        FileUpload::make('rimg')->label('Deliver Boy Image')
                            ->disk('s3')
                            ->visibility('public')
                            ->required()
                            ->directory('riders')
                            ->columnSpanFull()
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                                $path = 'riders/' . $filename;

                                $stream = $file->readStream();
                                $options = ['visibility' => 'public'];

                                Storage::disk('s3')->put($path, $stream, $options);
                                if (is_resource($stream)) {
                                    fclose($stream);
                                }

                                Log::info('S3 upload result', [
                                    'path' => $path,
                                    'exists' => Storage::disk('s3')->exists($path),
                                ]);

                                return $path;
                            }),

                       
                    ])->columns(2),
            
                
               
               
                
                
                
               
                

             

                


                
                
                // Forms\Components\TextInput::make('accept')
                //     ->required()
                //     ->numeric()
                //     ->default(0),
                // Forms\Components\TextInput::make('reject')
                //     ->required()
                //     ->numeric()
                //     ->default(0),
                // Forms\Components\TextInput::make('complete')
                //     ->required()
                //     ->numeric()
                //     ->default(0),
              
                Forms\Components\Section::make('Verificatio Info')
                    ->description('Provide legal information for verification')
                    ->aside()
                    ->icon('heroicon-m-check')
                        ->schema([
                            Forms\Components\TextInput::make('adhar_id')
                                ->required(),
                        ])->columns(1),
               

                   
                Forms\Components\Section::make('Address Information')
                    ->description('Address Information')
                    ->aside()
                    ->icon('heroicon-m-home')
                        ->schema([
                            Forms\Components\Textarea::make('full_address')
                                ->required(),
                            Forms\Components\TextInput::make('pincode')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Textarea::make('landmark')
                                ->required(),
                        ])->columns(1),

                Forms\Components\Section::make('Delivery Zone')
                ->description('Select a deilvery zone')
                ->aside()
                ->icon('heroicon-m-map-pin')
                    ->schema([
                        Forms\Components\Select::make('dzone')
                            ->relationship('zone','title')
                            ->label('Select Delivery Zone')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                  
                Forms\Components\Section::make('Payout Information')
                ->description('Enter you bank details for payout')
                ->aside()
                ->icon('heroicon-m-banknotes')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                        ->required(),
                    Forms\Components\TextInput::make('ifsc')
                        ->required(),
                    Forms\Components\TextInput::make('receipt_name')
                        ->required(),
                    Forms\Components\TextInput::make('acc_number')
                        ->required(),
                    Forms\Components\TextInput::make('upi_id'),
                    ])->columns(2),
                Forms\Components\Section::make('Razorpay (Optional)')
                ->description('Store mapped Razorpay Contact & Fund Account IDs for payouts')
                ->aside()
                ->icon('heroicon-m-credit-card')
                ->schema([
                    Forms\Components\TextInput::make('razorpay_contact_id')
                        ->label('RZP Contact ID')
                        ->placeholder('cont_XXXXXXXXXXXX')
                        ->maxLength(100)
                        ->rules(['nullable', 'regex:/^cont_[A-Za-z0-9]+$/'])
                        ->helperText('Example: cont_abc123...'),

                    Forms\Components\TextInput::make('razorpay_fund_account_id')
                        ->label('RZP Fund Account ID')
                        ->placeholder('fa_XXXXXXXXXXXX')
                        ->maxLength(100)
                        ->rules(['nullable', 'regex:/^fa_[A-Za-z0-9]+$/'])
                        ->helperText('Example: fa_abc123...'),
                ])->columns(2),


                

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('Account Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Published' : 'Unpublished')
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('rstatus')
                    ->label('Profile Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Active' : 'Inactive')
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'info',
                        '0' => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pincode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->searchable(),
                Tables\Columns\TextColumn::make('accept')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reject')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('complete')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('dzone')
                    ->label('Delivery Zone')
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
            'index' => Pages\ListRiders::route('/'),
            'create' => Pages\CreateRider::route('/create'),
            'edit' => Pages\EditRider::route('/{record}/edit'),
        ];
    }
}
