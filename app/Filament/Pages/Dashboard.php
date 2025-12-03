<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AllStats;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            AllStats::class,
        ];
    }
}
