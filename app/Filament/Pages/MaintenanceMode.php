<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class MaintenanceMode extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Maintenance Mode';
    protected static ?string $title           = 'Maintenance Mode';
    protected static ?string $navigationGroup = 'System';
    protected static ?int    $navigationSort  = 4;
    protected static string  $view            = 'filament.pages.maintenance-mode';

    public bool $isDown = false;

    public function mount(): void
    {
        $this->isDown = file_exists(storage_path('framework/down'));
    }

    public function enableMaintenance(): void
    {
        Artisan::call('down', ['--retry' => 60, '--refresh' => 60]);
        $this->isDown = true;
        Notification::make()
            ->title('Maintenance mode enabled')
            ->body('The application is now inaccessible to regular users.')
            ->warning()
            ->send();
    }

    public function disableMaintenance(): void
    {
        Artisan::call('up');
        $this->isDown = false;
        Notification::make()
            ->title('Application is back online')
            ->body('All users can now access the application.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        if ($this->isDown) {
            return [
                Action::make('bring_online')
                    ->label('Bring Application Online')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Bring Application Online?')
                    ->modalDescription('All users will be able to access the application immediately.')
                    ->action(fn() => $this->disableMaintenance()),
            ];
        }

        return [
            Action::make('enable_maintenance')
                ->label('Enable Maintenance Mode')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Enable Maintenance Mode?')
                ->modalDescription('Regular users will see a maintenance page. Admin panel remains accessible.')
                ->action(fn() => $this->enableMaintenance()),
        ];
    }
}
