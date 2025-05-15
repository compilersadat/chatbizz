<?php
namespace App\Filament\Resources\RiderResource\Api\Handlers;

use Illuminate\Http\Request;
use Rupadana\ApiService\Http\Handlers;
use App\Filament\Resources\RiderResource;
use App\Filament\Resources\RiderResource\Api\Requests\CreateRiderRequest;

class CreateHandler extends Handlers {
    public static string | null $uri = '/';
    public static string | null $resource = RiderResource::class;

    public static function getMethod()
    {
        return Handlers::POST;
    }

    public static function getModel() {
        return static::$resource::getModel();
    }

    /**
     * Create Rider
     *
     * @param CreateRiderRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(CreateRiderRequest $request)
    {
        $model = new (static::getModel());

        $model->fill($request->all());

        $model->save();

        return static::sendSuccessResponse($model, "Successfully Create Resource");
    }
}