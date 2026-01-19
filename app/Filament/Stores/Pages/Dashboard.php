<?php

namespace App\Filament\Stores\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.stores.pages.dashboard';

    protected static ?string $title = 'Stores Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Stores\Widgets\StoresStatsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
