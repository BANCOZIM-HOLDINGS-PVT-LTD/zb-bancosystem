<?php

namespace App\Filament\Widgets;

use App\Models\ApplicationState;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class ApplicationAnalyticsWidget extends ChartWidget
{
    protected static ?string $heading = 'Application Analytics';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '7days';

    protected function getData(): array
    {
        $period = $this->filter;

        switch ($period) {
            case '7days':
                return $this->getWeeklyData();
            case '30days':
                return $this->getMonthlyData();
            case '90days':
                return $this->getQuarterlyData();
            default:
                return $this->getWeeklyData();
        }
    }

    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 days',
            '30days' => 'Last 30 days',
            '90days' => 'Last 90 days',
        ];
    }

    private function getWeeklyData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M j');

            $applications = ApplicationState::whereDate('created_at', $date)->count();
            $completed = ApplicationState::whereDate('created_at', $date)
                ->where('current_step', 'completed')
                ->count();
            $approved = ApplicationState::whereDate('created_at', $date)
                ->where('current_step', 'approved')
                ->count();

            $data['applications'][] = $applications;
            $data['completed'][] = $completed;
            $data['approved'][] = $approved;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Applications Submitted',
                    'data' => $data['applications'],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Applications Completed',
                    'data' => $data['completed'],
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Applications Approved',
                    'data' => $data['approved'],
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    private function getMonthlyData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('M j');

            $applications = ApplicationState::whereDate('created_at', $date)->count();
            $completed = ApplicationState::whereDate('created_at', $date)
                ->where('current_step', 'completed')
                ->count();

            $data['applications'][] = $applications;
            $data['completed'][] = $completed;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Applications',
                    'data' => $data['applications'],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Completed',
                    'data' => $data['completed'],
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    private function getQuarterlyData(): array
    {
        $data = [];
        $labels = [];

        // Group by weeks for quarterly view
        for ($i = 12; $i >= 0; $i--) {
            $startDate = Carbon::now()->subWeeks($i)->startOfWeek();
            $endDate = Carbon::now()->subWeeks($i)->endOfWeek();
            $labels[] = $startDate->format('M j');

            $applications = ApplicationState::whereBetween('created_at', [$startDate, $endDate])->count();
            $completed = ApplicationState::whereBetween('created_at', [$startDate, $endDate])
                ->where('current_step', 'completed')
                ->count();

            $data['applications'][] = $applications;
            $data['completed'][] = $completed;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Applications',
                    'data' => $data['applications'],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Completed',
                    'data' => $data['completed'],
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}
