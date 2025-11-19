<?php

namespace App\Filament\Agent\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class AgentDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.agent.pages.agent-dashboard';

    protected static ?string $title = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Agent\Widgets\CommissionBalanceWidget::class,
            \App\Filament\Agent\Widgets\ClientCommissionsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
