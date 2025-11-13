<?php

namespace App\Filament\Resources\PDFManagementResource\Pages;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\ApplicationState;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PDFManagementStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalApplications = ApplicationState::whereNotNull('form_data')->count();
        $todayApplications = ApplicationState::whereDate('created_at', Carbon::today())
            ->whereNotNull('form_data')
            ->count();
        $completedApplications = ApplicationState::where('current_step', 'completed')
            ->whereNotNull('form_data')
            ->count();
        
        // Calculate storage usage
        $files = Storage::disk('local')->allFiles('applications');
        $totalSize = 0;
        foreach ($files as $file) {
            $totalSize += Storage::disk('local')->size($file);
        }
        
        $storageUsed = $this->formatBytes($totalSize);
        
        return [
            Stat::make('Total Applications', $totalApplications)
                ->description('All time applications with form data')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success'),
                
            Stat::make('Today\'s Applications', $todayApplications)
                ->description('Applications submitted today')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
                
            Stat::make('Completed Applications', $completedApplications)
                ->description('Fully completed applications')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
                
            Stat::make('Storage Used', $storageUsed)
                ->description('PDF files storage space')
                ->descriptionIcon('heroicon-m-server-stack')
                ->color('warning'),
        ];
    }
    
    private function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
}