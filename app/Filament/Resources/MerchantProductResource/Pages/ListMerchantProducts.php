<?php

namespace App\Filament\Resources\MerchantProductResource\Pages;

use App\Filament\Resources\MerchantProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMerchantProducts extends ListRecords
{
    protected static string $resource = MerchantProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
