<?php

namespace App\Filament\Resources\PDFManagementResource\Pages;

use App\Filament\Resources\PDFManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\ApplicationState;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ListPDFManagement extends ListRecords
{
    protected static string $resource = PDFManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cleanup')
                ->label('Cleanup Old Files')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->form([
                    \Filament\Forms\Components\TextInput::make('days')
                        ->label('Delete files older than (days)')
                        ->numeric()
                        ->default(30)
                        ->required()
                        ->helperText('This will permanently delete PDF files older than the specified number of days.'),
                ])
                ->action(function (array $data) {
                    $response = app(\App\Http\Controllers\Admin\PDFManagementController::class)
                        ->cleanup(new \Illuminate\Http\Request($data));
                    
                    $responseData = $response->getData(true);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Cleanup Completed')
                        ->body("Deleted {$responseData['files_deleted']} files")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalDescription('This action will permanently delete old PDF files to free up storage space.'),
                
            Actions\Action::make('statistics')
                ->label('View Statistics')
                ->icon('heroicon-o-chart-bar')
                ->url(route('admin.pdf.statistics'))
                ->openUrlInNewTab(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PDFManagementStatsWidget::class,
        ];
    }
}