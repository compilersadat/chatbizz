<?php

namespace App\Filament\Resources\RiderResource\Api\Handlers;

use App\Filament\Resources\SettingResource;
use App\Filament\Resources\RiderResource;
use Rupadana\ApiService\Http\Handlers;
use Spatie\QueryBuilder\QueryBuilder;
use Illuminate\Http\Request;
use App\Filament\Resources\RiderResource\Api\Transformers\RiderTransformer;

class DetailHandler extends Handlers
{
    public static string | null $uri = '/{id}';
    public static string | null $resource = RiderResource::class;


    /**
     * Show Rider
     *
     * @param Request $request
     * @return RiderTransformer
     */
    public function handler(Request $request)
    {
        $id = $request->route('id');
        
        $query = static::getEloquentQuery();

        $query = QueryBuilder::for(
            $query->where(static::getKeyName(), $id)
        )
            ->first();

        if (!$query) return static::sendNotFoundResponse();

        return new RiderTransformer($query);
    }
}
