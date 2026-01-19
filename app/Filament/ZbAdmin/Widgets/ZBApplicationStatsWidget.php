<?php

namespace App\Filament\ZbAdmin\Widgets;

use App\Models\ApplicationState;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ZBApplicationStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // ZB-specific applications only (exclude SSB)
        $zbApplications = ApplicationState::where(function ($query) {
            // ZB Account Opening applications
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.wantsAccount')) = 'true'")
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.hasAccount')) = 'true'");
        })->where(function ($query) {
            // Exclude SSB applications
            $query->whereRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(form_data, '$.employer')), '') != 'government-ssb'");
        });

        $totalZBApplications = (clone $zbApplications)->count();
        $pendingReview = (clone $zbApplications)->where('current_step', 'in_review')->count();
        $approved = (clone $zbApplications)->where('current_step', 'approved')->count();
        $rejected = (clone $zbApplications)->where('current_step', 'rejected')->count();
        
        // Today's ZB applications
        $todayApplications = (clone $zbApplications)->whereDate('created_at', today())->count();
        
        // Calculate approval rate
        $processedCount = $approved + $rejected;
        $approvalRate = $processedCount > 0 ? round(($approved / $processedCount) * 100) : 0;

        // Urgent applications (pending > 3 days)
        $urgentCount = (clone $zbApplications)
            ->where('current_step', 'in_review')
            ->where('created_at', '<', now()->subDays(3))
            ->count();

        return [
            Stat::make('ZB Applications Pending Review', $pendingReview)
                ->description($urgentCount > 0 ? "{$urgentCount} urgent (>3 days)" : 'All current')
                ->descriptionIcon($urgentCount > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-clock')
                ->color($urgentCount > 0 ? 'danger' : 'warning')
                ->chart([$rejected, $approved, $pendingReview]),

            Stat::make('Approved Today', ApplicationState::whereDate('updated_at', today())
                    ->where('current_step', 'approved')->count())
                ->description("Out of {$todayApplications} received")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('ZB Approval Rate', "{$approvalRate}%")
                ->description("{$approved} approved, {$rejected} rejected")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($approvalRate >= 70 ? 'success' : ($approvalRate >= 50 ? 'warning' : 'danger')),

            Stat::make('Total ZB Applications', $totalZBApplications)
                ->description('Account opening & holder loans')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
        ];
    }
}
