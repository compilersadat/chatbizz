<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\VehicleResource\RelationManagers;
use App\Support\StorageFallback;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextareaEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\BadgeEntry;
use Filament\Infolists\Components\Section;
use Filament\Forms\Components\Card;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    } 

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Delivery';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Vehicle Title')
                    ->required()
                    ->columnSpanFull(),
                    FileUpload::make('img')->label('Vehicle Image')
                    ->disk('s3')
                    ->directory('vehicles')
                    ->required()
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = 'vehicles/' . $filename;

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
                    Forms\Components\Select::make('status')
                    ->label('Vehicle Status')
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ])
                    ->required(),
                
                Forms\Components\TextInput::make('ukms')
                    ->label('Base Delivery Distance')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('uprice')
                    ->label('Base Delivery Charge')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('aprice')
                    ->label('Extra Delivery Charge')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('capcity')
                    ->label('Vehicle Capacity')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('size')
                    ->label('Vehicle Size')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ttime')
                    ->label('Time Taken 1 Km Approx')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('description')
                    ->label('Vehicle Description')
                    ->required()
                    ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Published' : 'Unpublished')
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('ukms')
                    ->label('Base Delivery Distance')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('uprice')
                    ->label('Base Delivery Charge')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('aprice')
                    ->label('Extra Delivery Charge')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ttime')
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
                Tables\Actions\ViewAction::make(),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
        ->schema([
            Section::make()
            ->schema([
                TextEntry::make('title')
                    ->label('Title')
                    ->columnSpanFull(),
                ImageEntry::make('img')
                    ->label('Image')
                    ->getStateUsing(fn ($record) => StorageFallback::url($record->img))
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => fn ($state) => $state === 1,
                        'danger' => fn ($state) => $state === 0,
                    ])
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Published' : 'Unpublished'),
                TextEntry::make('description')
                    ->label('Description')
                    ->columnSpanFull(),
                TextEntry::make('ukms')
                    ->label('Base Delivery Distance')
                    ->numeric(),
                TextEntry::make('uprice')
                    ->label('Used Price')
                    ->numeric(),
                TextEntry::make('aprice')
                    ->label('Asking Price')
                    ->numeric(),
                TextEntry::make('capcity')
                    ->label('Capacity')
                    ->columnSpanFull(),
                TextEntry::make('size')
                    ->label('Size')
                    ->columnSpanFull(),
                TextEntry::make('ttime')
                    ->label('Travel Time')
                    ->numeric(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
