<?php

namespace App\Filament\Partner\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.partner.pages.dashboard';

    protected static ?string $title = 'Partner Dashboard';

    public function getWidgets(): array
    {
        // Mirror Super Admin widgets (read-only view)
        return [
            \App\Filament\Widgets\ApplicationStatsWidget::class,
            \App\Filament\Widgets\ProductStatsWidget::class,
            \App\Filament\Widgets\RecentApplicationsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
