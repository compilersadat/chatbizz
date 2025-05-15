<?php

namespace App\Filament\Resources\MerchantCatagoryResource\Pages;

use App\Filament\Resources\MerchantCatagoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMerchantCatagory extends EditRecord
{
    protected static string $resource = MerchantCatagoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
