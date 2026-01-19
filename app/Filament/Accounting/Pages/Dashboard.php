<?php

namespace App\Filament\Accounting\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.accounting.pages.dashboard';

    protected static ?string $title = 'Accounting Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Accounting\Widgets\FinancialOverviewWidget::class,
            \App\Filament\Accounting\Widgets\CashFlowWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 2;
    }
}
