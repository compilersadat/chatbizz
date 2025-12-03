<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ManagerResource\Pages;
use App\Filament\Resources\ManagerResource\RelationManagers;
use App\Models\Manager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;


class ManagerResource extends Resource
{
    protected static ?string $model = Manager::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Admin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('img')
                    ->label('Zone Manager Image')
                    ->disk('s3')
                    ->visibility('public')
                    ->directory('managers')
                    ->required()
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                        $path = 'managers/' . $filename;

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
                Forms\Components\Select::make('status')
                    ->label('Manager Status')
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ])
                    ->required(),

                    Forms\Components\TextInput::make('mobile')
                    ->label('Phone number')
                    ->tel()
                    ->maxLength(10) 
                    ->required(),
                    
                    // Forms\Components\TextInput::make('mobile')
                    //     ->label('Phone Number')
                    //     ->tel()
                    //     ->required()
                    //     ->maxLength(14) // (XXX) XXX-XXXX = 14 characters
                    //     ->placeholder('(XXX) XXX-XXXX')
                    //     ->formatStateUsing(function ($state) {
                    //         if (!$state)
                    //             return '';
                    //         // Format stored number to display format
                    //         return sprintf(
                    //             "(%s) %s-%s",
                    //             substr($state, 0, 3),
                    //             substr($state, 3, 3),
                    //             substr($state, 6)
                    //         );
                    //     })
                    //     ->dehydrateStateUsing(function ($state) {
                    //         // Remove all non-numeric characters before saving
                    //         return preg_replace('/[^0-9]/', '', $state);
                    //     })
                    //     ->rules([
                    //         'regex:/^\(\d{3}\)\s\d{3}-\d{4}$/',
                    //         'min:14',
                    //         'max:14'
                    //     ])
                    //     ->validationMessages([
                    //         'regex' => 'Please enter a valid phone number in (XXX) XXX-XXXX format',
                    //     ]),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->maxLength(255)
                    ->revealable(),

                Select::make('zone_id')
                    ->relationship('zone','title')
                    ->searchable()
                    ->preload(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                // Tables\Columns\TextColumn::make('img')
                //     ->searchable(),
                    Tables\Columns\TextColumn::make('status')
                    ->label('Manager Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Published' : 'Unpublished')
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'success',
                        '0' => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('zone.title')
                    ->label('zone')
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
            'index' => Pages\ListManagers::route('/'),
            'create' => Pages\CreateManager::route('/create'),
            'edit' => Pages\EditManager::route('/{record}/edit'),
        ];
    }
}
