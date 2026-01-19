<?php

namespace App\Filament\ZbAdmin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.zb-admin.pages.dashboard';

    protected static ?string $title = 'ZB Admin Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\ZbAdmin\Widgets\ZBApplicationStatsWidget::class,
            \App\Filament\ZbAdmin\Widgets\PendingZBApprovalsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
