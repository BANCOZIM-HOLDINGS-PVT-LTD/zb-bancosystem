<?php

namespace App\Filament\Widgets;

use App\Models\ApplicationState;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class ChannelPerformanceWidget extends ChartWidget
{
    protected static ?string $heading = 'Channel Performance';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $channels = ['web', 'whatsapp', 'ussd', 'mobile_app'];
        $channelLabels = [
            'web' => 'Web Portal',
            'whatsapp' => 'WhatsApp',
            'ussd' => 'USSD',
            'mobile_app' => 'Mobile App'
        ];
        
        $data = [];
        $labels = [];
        $colors = [];
        
        foreach ($channels as $channel) {
            $total = ApplicationState::where('channel', $channel)->count();
            $completed = ApplicationState::where('channel', $channel)
                ->where('current_step', 'completed')
                ->count();
            
            $conversionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
            
            $data[] = $conversionRate;
            $labels[] = $channelLabels[$channel] ?? ucfirst($channel);
            
            // Assign colors based on performance
            if ($conversionRate >= 70) {
                $colors[] = '#10B981'; // Green
            } elseif ($conversionRate >= 50) {
                $colors[] = '#F59E0B'; // Yellow
            } else {
                $colors[] = '#EF4444'; // Red
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Conversion Rate (%)',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
                    'max' => 100,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                ],
            ],
        ];
    }
}
