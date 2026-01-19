<?php

namespace App\Filament\Hr\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.hr.pages.dashboard';

    protected static ?string $title = 'HR Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Hr\Widgets\HRStatsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
