<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AllStats extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $liveOrders = Order::query()
            ->whereNotIn('status', [
                'completed',
                'delivered',
                'cancelled',
                'canceled',
                'rejected',
                'failed',
                'refunded',
            ])
            ->count();

        return [
            Stat::make('Customers', Customer::count())
                ->description('Total customers')
                ->icon('heroicon-o-user-group'),
            Stat::make('Products', Product::count())
                ->description('Products in catalog')
                ->icon('heroicon-o-cube'),
            Stat::make('Live Orders', $liveOrders)
                ->description('Orders currently in progress')
                ->icon('heroicon-o-bolt')
                ->color('warning'),
        ];
    }
}
