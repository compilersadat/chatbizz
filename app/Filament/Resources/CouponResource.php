<?php

namespace App\Filament\Resources;

use App\Filament\Components\FallbackFileUpload;
use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers;
use App\Support\StorageFallback;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action;
 

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    } 
    
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Coupon Information')
                ->icon('heroicon-m-identification')
                ->schema([

                    Forms\Components\TextInput::make('c_title')
                        ->label('Coupon title')
                        ->required(),
                    Forms\Components\TextInput::make('subtitle')
                        ->label('Coupon subtitle')
                        ->required(),

                    Forms\Components\Textarea::make('c_desc')
                        ->label('Coupon Description')
                        ->required()
                        ->columnSpanFull(),

                    FallbackFileUpload::make('c_img')
                        ->label('Coupon Image')
                        ->disk('s3')
                        ->visibility('public')
                        ->required()
                        ->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Section::make('Coupon Conditions')
                    ->description('Copuon conditions')
                    ->aside()
                    ->icon('heroicon-m-home')
                        ->schema([


                            Forms\Components\DatePicker::make('cdate')
                                ->label('Coupon Expiry Date')
                                ->required(),
                        
                            Forms\Components\TextInput::make('c_value')
                                ->label('Coupon Value')
                                ->required(),
                        
                            Forms\Components\Select::make('status')
                                ->label('Coupon Status')
                                ->options([
                                    1 => 'Published',
                                    0 => 'Unpublished',
                                ])
                                ->required()->columnSpanFull(),


                            

                                Forms\Components\TextInput::make('ctitle')
                                    ->label('Custom Title')
                                    ->required()
                                    ->reactive() // Makes the field reactive to updates
                                    ->suffixAction( // Adds a button beside the field
                                        Action::make('generateCode')
                                            ->label('Generate')
                                            ->icon('heroicon-o-arrow-path-rounded-square')
                                            ->action(function (Action $action, array $data, $set) {
                                                // Generate an 8-character alphanumeric code
                                                $randomCode = strtoupper(bin2hex(random_bytes(4))); // 4 bytes = 8 hex characters
                                                $set('ctitle', $randomCode); // Set the generated code to the 'ctitle' field
                                            })
                                    ),

                            Forms\Components\TextInput::make('min_amt')
                                ->label('Coupon Min Order Amount')
                                ->required()
                                ->numeric(),

                        ])->columns(2),
               
                
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('ctitle')
                    ->sortable(),

 

                Tables\Columns\ImageColumn::make('c_img')
                    ->getStateUsing(fn ($record) => StorageFallback::url($record->c_img))
                    ->circular(),

                Tables\Columns\TextColumn::make('cdate')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Published' : 'Unpublished')
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('min_amt')
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
