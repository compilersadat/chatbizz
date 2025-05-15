<?php
namespace App\Filament\Resources\RiderResource\Api;

use Rupadana\ApiService\ApiService;
use App\Filament\Resources\RiderResource;
use Illuminate\Routing\Router;


class RiderApiService extends ApiService
{
    protected static string | null $resource = RiderResource::class;

    public static function handlers() : array
    {
        return [
            Handlers\CreateHandler::class,
            Handlers\UpdateHandler::class,
            Handlers\DeleteHandler::class,
            Handlers\PaginationHandler::class,
            Handlers\DetailHandler::class
        ];

    }
}
