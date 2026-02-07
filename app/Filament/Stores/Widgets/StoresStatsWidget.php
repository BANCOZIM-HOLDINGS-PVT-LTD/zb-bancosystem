<?php

namespace App\Filament\Stores\Widgets;

use App\Models\DeliveryTracking;
use App\Models\ProductInventory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoresStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Pending deliveries
        $pendingDeliveries = DeliveryTracking::where('status', 'pending')->count();
        
        // Processing deliveries
        $processingDeliveries = DeliveryTracking::where('status', 'processing')->count();
        
        // Dispatched deliveries
        $dispatchedDeliveries = DeliveryTracking::where('status', 'dispatched')->count();
        
        // Delivered today
        $deliveredToday = DeliveryTracking::where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();

        // Low stock items (if ProductInventory exists)
        $lowStockCount = 0;
        try {
            $lowStockCount = ProductInventory::where('quantity', '<=', 5)->count();
        } catch (\Exception $e) {
            // ProductInventory may not exist
        }

        return [
            Stat::make('Pending Deliveries', $pendingDeliveries)
                ->description('Awaiting processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingDeliveries > 10 ? 'danger' : ($pendingDeliveries > 5 ? 'warning' : 'success')),

            Stat::make('Processing', $processingDeliveries)
                ->description('Being prepared')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info'),

            Stat::make('Dispatched', $dispatchedDeliveries)
                ->description('In transit')
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary'),

            Stat::make('Delivered Today', $deliveredToday)
                ->description('Completed deliveries')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Low Stock Items', $lowStockCount)
                ->description('Items below threshold')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}

