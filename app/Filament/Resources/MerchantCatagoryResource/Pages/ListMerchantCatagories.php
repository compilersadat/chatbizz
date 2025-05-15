<?php

namespace App\Filament\Resources\MerchantCatagoryResource\Pages;

use App\Filament\Resources\MerchantCatagoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMerchantCatagories extends ListRecords
{
    protected static string $resource = MerchantCatagoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
