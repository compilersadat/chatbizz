<?php
namespace App\Filament\Resources\RiderResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Rider;

/**
 * @property Rider $resource
 */
class RiderTransformer extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->resource->toArray();
    }
}
