<?php

namespace App\Filament\Widgets;

use App\Models\ApplicationState;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApplicationStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '15s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Get total applications count
        $totalApplications = ApplicationState::count();
        $completedApplications = ApplicationState::where('current_step', 'completed')->count();
        $completionRate = $totalApplications > 0 ? round(($completedApplications / $totalApplications) * 100) : 0;

        // Get today's applications
        $todayApplications = ApplicationState::whereDate('created_at', today())->count();
        $yesterdayApplications = ApplicationState::whereDate('created_at', today()->subDay())->count();
        $todayChange = $yesterdayApplications > 0
            ? round((($todayApplications - $yesterdayApplications) / $yesterdayApplications) * 100)
            : ($todayApplications > 0 ? 100 : 0);

        // Get this week's applications
        $thisWeekApplications = ApplicationState::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->count();

        $lastWeekApplications = ApplicationState::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
        ])->count();

        $weeklyChange = $lastWeekApplications > 0
            ? round((($thisWeekApplications - $lastWeekApplications) / $lastWeekApplications) * 100)
            : ($thisWeekApplications > 0 ? 100 : 0);

        // Get applications by status
        $pendingReview = ApplicationState::where('current_step', 'in_review')->count();
        $approved = ApplicationState::where('current_step', 'approved')->count();
        $rejected = ApplicationState::where('current_step', 'rejected')->count();
        $pendingDocuments = ApplicationState::where('current_step', 'pending_documents')->count();
        $processing = ApplicationState::where('current_step', 'processing')->count();

        // Get average loan amount (for all applications, not just completed)
        $averageLoanAmount = ApplicationState::get()
            ->avg(function ($application) {
                return $application->form_data['finalPrice'] ?? 0;
            });

        // Get total loan value
        $totalLoanValue = ApplicationState::get()
            ->sum(function ($application) {
                return $application->form_data['finalPrice'] ?? 0;
            });

        // Get applications by channel
        $webApplications = ApplicationState::where('channel', 'web')->count();
        $whatsappApplications = ApplicationState::where('channel', 'whatsapp')->count();
        $ussdApplications = ApplicationState::where('channel', 'ussd')->count();
        $mobileAppApplications = ApplicationState::where('channel', 'mobile_app')->count();

        // Get approval rate
        $processedApplications = $approved + $rejected;
        $approvalRate = $processedApplications > 0 ? round(($approved / $processedApplications) * 100) : 0;

        // Get average processing time for completed applications
        $avgProcessingTime = ApplicationState::whereIn('current_step', ['approved', 'rejected', 'completed'])
            ->get()
            ->avg(function ($application) {
                return $application->created_at->diffInHours($application->updated_at);
            });

        // Get applications requiring urgent attention (older than 3 days in review)
        $urgentApplications = ApplicationState::where('current_step', 'in_review')
            ->where('created_at', '<', now()->subDays(3))
            ->count();

        return [
            Stat::make('Total Applications', number_format($totalApplications))
                ->description($completionRate.'% completion rate')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([
                    $completedApplications,
                    $totalApplications - $completedApplications,
                ]),

            Stat::make('Today\'s Applications', $todayApplications)
                ->description($todayChange >= 0 ? '+'.$todayChange.'% from yesterday' : $todayChange.'% from yesterday')
                ->descriptionIcon($todayChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($todayChange >= 0 ? 'success' : 'danger'),

            Stat::make('This Week', $thisWeekApplications)
                ->description($weeklyChange >= 0 ? '+'.$weeklyChange.'% from last week' : $weeklyChange.'% from last week')
                ->descriptionIcon($weeklyChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($weeklyChange >= 0 ? 'success' : 'danger'),

            Stat::make('Pending Review', $pendingReview)
                ->description($urgentApplications > 0 ? $urgentApplications.' urgent (>3 days)' : 'All current')
                ->descriptionIcon('heroicon-m-clock')
                ->color($urgentApplications > 0 ? 'danger' : 'warning'),

            Stat::make('Approval Rate', $approvalRate.'%')
                ->description($approved.' approved, '.$rejected.' rejected')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($approvalRate >= 70 ? 'success' : ($approvalRate >= 50 ? 'warning' : 'danger'))
                ->chart([$approved, $rejected]),

            Stat::make('Total Loan Value', '$'.number_format($totalLoanValue, 2))
                ->description('Avg: $'.number_format($averageLoanAmount, 2))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Processing Time', round($avgProcessingTime, 1).' hrs')
                ->description('Average processing time')
                ->descriptionIcon('heroicon-m-clock')
                ->color($avgProcessingTime <= 24 ? 'success' : ($avgProcessingTime <= 72 ? 'warning' : 'danger')),

            Stat::make('Active Pipeline', $pendingReview + $processing + $pendingDocuments)
                ->description($processing.' processing, '.$pendingDocuments.' pending docs')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color('info'),
        ];
    }
}
