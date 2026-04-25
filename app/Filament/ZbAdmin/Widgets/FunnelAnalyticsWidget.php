<?php

namespace App\Filament\ZbAdmin\Widgets;

use App\Models\ApplicationState;
use App\Models\StateTransition;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FunnelAnalyticsWidget extends ChartWidget
{
    protected static ?string $heading = 'Application Funnel';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';
    
    public ?string $filter = '30days';

    protected function getFilters(): ?array
    {
        return [
            '7days' => 'Last 7 days',
            '30days' => 'Last 30 days',
            '90days' => 'Last 90 days',
            'all' => 'All Time',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        
        $dateFilter = function ($query) use ($activeFilter) {
            if ($activeFilter === '7days') {
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
            } elseif ($activeFilter === '30days') {
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
            } elseif ($activeFilter === '90days') {
                $query->where('created_at', '>=', Carbon::now()->subDays(90));
            }
        };

        // Define the major funnel steps in logical order
        $steps = [
            'product' => 'Product Selection',
            'delivery' => 'Delivery Details',
            'registration' => 'Registration',
            'employer' => 'Employment Info',
            'account' => 'Account Info',
            'summary' => 'Review Summary',
            'completed' => 'Completed'
        ];

        $counts = [];
        $labels = [];

        foreach ($steps as $step => $label) {
            $count = ApplicationState::where(function($query) use ($step) {
                $query->where('current_step', $step)
                      ->orWhereExists(function ($q) use ($step) {
                          $q->select(DB::raw(1))
                            ->from('state_transitions')
                            ->whereColumn('state_transitions.state_id', 'application_states.id')
                            ->where('state_transitions.to_step', $step);
                      });
            })
            ->when($activeFilter !== 'all', $dateFilter)
            ->count();

            $counts[] = $count;
            $labels[] = $label;
        }

        // Calculate drop-off percentages for the labels
        $formattedLabels = [];
        $total = $counts[0] > 0 ? $counts[0] : 1;
        
        foreach ($labels as $index => $label) {
            $percentage = round(($counts[$index] / $total) * 100);
            $formattedLabels[] = "{$label} ({$percentage}%)";
        }

        return [
            'datasets' => [
                [
                    'label' => 'Users reached this stage',
                    'data' => $counts,
                    'backgroundColor' => [
                        '#3B82F6', // Blue 500
                        '#60A5FA', // Blue 400
                        '#93C5FD', // Blue 300
                        '#BFDBFE', // Blue 200
                        '#DBEAFE', // Blue 100
                        '#EFF6FF', // Blue 50
                        '#10B981', // Emerald 500 (for Completed)
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $formattedLabels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y', // Horizontal bar chart looks better for funnels
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
