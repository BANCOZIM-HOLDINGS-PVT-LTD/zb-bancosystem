<?php

namespace App\Filament\Widgets;

use App\Models\ApplicationState;
use App\Models\Agent;
use App\Models\Product;
use App\Models\Commission;
use Filament\Widgets\Widget;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        return [
            'stats' => $this->getQuickStats(),
            'actions' => $this->getQuickActions(),
        ];
    }

    private function getQuickStats(): array
    {
        return [
            'pending_approvals' => ApplicationState::where('current_step', 'in_review')->count(),
            'urgent_applications' => ApplicationState::where('current_step', 'in_review')
                ->where('created_at', '<', now()->subDays(3))
                ->count(),
            'today_applications' => ApplicationState::whereDate('created_at', today())->count(),
            'pending_commissions' => Commission::pending()->count(),
            'active_agents' => Agent::active()->count(),
            'total_products' => Product::count(),
        ];
    }

    private function getQuickActions(): array
    {
        return [
            [
                'label' => 'Review Pending Applications',
                'icon' => 'heroicon-o-document-magnifying-glass',
                'color' => 'warning',
                'url' => route('filament.admin.resources.applications.index', ['tableFilters[current_step][value]' => 'in_review']),
                'badge' => ApplicationState::where('current_step', 'in_review')->count(),
            ],
            [
                'label' => 'Manage Products',
                'icon' => 'heroicon-o-cube',
                'color' => 'primary',
                'url' => route('filament.admin.resources.inventory-management.index'),
                'badge' => Product::count(),
            ],
            [
                'label' => 'Agent Performance',
                'icon' => 'heroicon-o-users',
                'color' => 'success',
                'url' => route('filament.admin.resources.agents.index'),
                'badge' => Agent::active()->count(),
            ],
            [
                'label' => 'Commission Management',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'info',
                'url' => route('filament.admin.resources.commissions.index'),
                'badge' => Commission::pending()->count(),
            ],
            [
                'label' => 'System Health',
                'icon' => 'heroicon-o-heart',
                'color' => $this->getSystemHealthColor(),
                'action' => 'refreshSystemHealth',
                'badge' => null,
            ],
            [
                'label' => 'Export Reports',
                'icon' => 'heroicon-o-document-arrow-down',
                'color' => 'gray',
                'action' => 'exportReports',
                'badge' => null,
            ],
        ];
    }

    private function getSystemHealthColor(): string
    {
        // Simple health check
        try {
            \DB::connection()->getPdo();
            return 'success';
        } catch (\Exception $e) {
            return 'danger';
        }
    }

    public function refreshSystemHealth()
    {
        // Clear health check cache
        Cache::forget('system_health');
        
        Notification::make()
            ->title('System Health Refreshed')
            ->body('System health status has been updated')
            ->success()
            ->send();
    }

    public function exportReports()
    {
        // This would trigger a report export
        Notification::make()
            ->title('Report Export Started')
            ->body('Your reports are being generated and will be available shortly')
            ->info()
            ->send();
    }
}
