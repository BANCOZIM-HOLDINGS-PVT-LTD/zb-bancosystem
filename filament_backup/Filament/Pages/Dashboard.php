<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\QuickActionsWidget::class,
            \App\Filament\Widgets\ApplicationStatsWidget::class,
            \App\Filament\Widgets\AgentPerformanceWidget::class,
            \App\Filament\Widgets\ProductStatsWidget::class,
            \App\Filament\Widgets\ApplicationAnalyticsWidget::class,
            \App\Filament\Widgets\ChannelPerformanceWidget::class,
            \App\Filament\Widgets\PendingApprovalsWidget::class,
            \App\Filament\Widgets\RecentApplicationsWidget::class,
            \App\Filament\Widgets\SystemHealthWidget::class,
        ];
    }
}
