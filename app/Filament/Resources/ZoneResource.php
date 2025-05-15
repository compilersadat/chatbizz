<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZoneResource\Pages;
use App\Filament\Resources\ZoneResource\RelationManagers;
use App\Models\Zone;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms\Set;
use Filament\Forms\Get;
// use Filament\Forms\Components\Select;
use Filament\Resources\Select;
use Illuminate\Support\Facades\DB;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Enums\Srid;

class ZoneResource extends Resource
{
    protected static ?string $model = Zone::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-europe-africa';
    protected static ?string $navigationGroup = 'Delivery';

    public static function form(Form $form): Form
    { $coordinate = '';
        return $form
            ->schema([

                
               
    // Forms\Components\TextInput::make('coordinates')
    // ->label('coordinates'),
 

    Forms\Components\Textarea::make('coordinates')
    ->label('Coordinates')
    ->required()
    // ->placeholder('Enter coordinates in WKT format, e.g., POLYGON((lng1 lat1, lng2 lat2, ...))')
    // ->rows(4)
    // ->helperText('Enter the polygon coordinates in WKT format. Example: POLYGON((78.44 17.43, 78.45 17.43, 78.44 17.43, 78.44 17.43))')
    
    // ->afterStateUpdated(function (Set $set, ?string $state): void {
    //     if ($state && !str_starts_with($state, 'POLYGON(')) {
    //         $set('coordinates', ''); // Reset if input is invalid
    //     }
    // })
    ,


    // Forms\Components\TextInput::make('geojson')
    // ->label('geojson'),

    // Forms\Components\TextInput::make('wkt')
    // ->label('wkt'),
 

    Map::make('location')
    ->label('Location')
    ->columnSpanFull()
    ->defaultLocation(latitude: 40.4168, longitude: -3.7038)
    ->afterStateUpdated(function (Set $set, Get $get, ?array $state, $record): void {
        // Save lat/lng
        $set('latitude', $state['lat'] ?? null);
        $set('longitude', $state['lng'] ?? null);

        // Save GeoJSON data
        $set('geojson', json_encode($state));

      
        // DB::table('zones')->insert([
        //     'title' => 'Test Zone',
        //     'status' => 1,
        //     'coordinates' => DB::raw("ST_GeomFromText('POLYGON((12.455363273620605 41.90746728266806, 12.450309991836548 41.906636872349075, 12.445632219314575 41.90197359839437, 12.447413206100464 41.90027269624499, 12.457906007766724 41.90000118654431, 12.458517551422117 41.90281205461268, 12.457584142684937 41.903107507989986, 12.457734346389769 41.905918239316286, 12.45572805404663 41.90637337450963, 12.455363273620605 41.90746728266806))')"),
        //     'alias' => 'Vatican Area',
        // ]);
        // dd("testing");       
        // Handle GeoJSON FeatureCollection format
        if (isset($state['geojson']['type']) && $state['geojson']['type'] === 'FeatureCollection') {
            $features = $state['geojson']['features'] ?? [];
            
                foreach ($features as $feature) {
                    // dd($feature['geometry']);
                    if ($feature['geometry']['type'] === 'Polygon') {
                        $coordinates = $feature['geometry']['coordinates'][0] ?? [];
                        $wkt = 'POLYGON((' . implode(',', array_map(function ($coord) {
                            return implode(' ', $coord);
                        }, $coordinates)) . '))';
                        $set('coordinates', $wkt);
                        break;
                    }
                }
        }
        

        // Save alias coordinates
        $lat = $state['lat'] ?? null;
        $lng = $state['lng'] ?? null;
        $oldCoordinate = $get('alias');
        $newCoordinate = $lat && $lng ? "({$lat},{$lng})" : null;

        $coordinate = $oldCoordinate && $newCoordinate
            ? $oldCoordinate . ',' . $newCoordinate
            : $newCoordinate;

        $set('alias', $coordinate);
    })
    ->afterStateHydrated(function (Set $set, Get $get, $state, $record): void {
        if ($record && !empty($record->coordinates)) {
            $geojson = DB::selectOne("SELECT ST_AsGeoJSON(coordinates) AS geojson FROM zones WHERE id = ?", [$record->id])->geojson ?? '{}';
    
            if ($geojson) {
                $geojsonDecoded = json_decode($geojson, true);
                if (isset($geojsonDecoded['coordinates'])) {
                    $geojsonFeatureCollection = [
                        'type'     => 'FeatureCollection',
                        'features' => [
                            [
                                'type'       => 'Feature',
                                'geometry'   => $geojsonDecoded,
                                'properties' => []
                            ]
                        ]
                    ];
                }
                $set('location', [
                    'lat' => $geojsonDecoded['coordinates'][0][0][1],
                    'lng' => $geojsonDecoded['coordinates'][0][0][0],
                    'geojson' => $geojsonFeatureCollection
                ]);
            }
        }
    })    
    ->extraStyles([
        'min-height: 50vh',
        'border-radius: 50px',
    ])
    ->liveLocation(true, true, 5000)
    ->showMarker()
    ->markerColor("#22c55eff")
    ->markerHtml('<div class="custom-marker">...</div>')
    ->markerIconUrl('/path/to/marker.png')
    ->markerIconSize([32, 32])
    ->markerIconClassName('my-marker-class')
    ->markerIconAnchor([16, 32])
    ->showFullscreenControl()
    ->showZoomControl()
    ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")
    ->zoom(15)
    ->clickable(true)
    ->geoMan(true) // Enable drawing tools
    ->geoManEditable(true) // Make drawn features editable
    ->geoManPosition('topleft') // Position the drawing toolbar
    ->drawPolygon() // Enable drawing polygons
    ->editPolygon() // Allow polygon editing
    ->deleteLayer() // Allow deleting drawn layers
    ->setColor('#3388ff')
    ->setFilledColor('#cad9ec'),

    
                Forms\Components\TextInput::make('latitude')
                    ->hiddenLabel()
                    ->hidden(),
            
                Forms\Components\TextInput::make('longitude')
                    ->hiddenLabel()
                    ->hidden(),



    Forms\Components\Textarea::make('title')
                    ->required()
                    ->columnSpanFull(),
                    Forms\Components\Select::make('status')
                    ->label('Vehicle Status')
                    ->options([
                        1 => 'Published',
                        0 => 'Unpublished',
                    ])
                    ->required(),
                
                Forms\Components\Textarea::make('alias')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('status')
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
            'index' => Pages\ListZones::route('/'),
            'create' => Pages\CreateZone::route('/create'),
            'edit' => Pages\EditZone::route('/{record}/edit'),
        ];
    }
}
