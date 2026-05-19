<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;

class CacheManager extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-circle-stack';
    protected static ?string $navigationLabel = 'Cache Manager';
    protected static ?string $title           = 'Cache Manager';
    protected static ?string $navigationGroup = 'System';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.pages.cache-manager';

    public function getCacheInfo(): array
    {
        return [
            [
                'label'   => 'Cache Driver',
                'value'   => strtoupper(config('cache.default', 'file')),
                'icon'    => 'heroicon-o-circle-stack',
                'color'   => 'text-blue-600',
                'bg'      => 'bg-blue-50 dark:bg-blue-900/20',
            ],
            [
                'label'   => 'Config Cache',
                'value'   => file_exists(base_path('bootstrap/cache/config.php')) ? 'Cached' : 'Not Cached',
                'icon'    => 'heroicon-o-cog-6-tooth',
                'color'   => file_exists(base_path('bootstrap/cache/config.php')) ? 'text-green-600' : 'text-gray-400',
                'bg'      => file_exists(base_path('bootstrap/cache/config.php')) ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-800',
            ],
            [
                'label'   => 'Route Cache',
                'value'   => file_exists(base_path('bootstrap/cache/routes-v7.php')) ? 'Cached' : 'Not Cached',
                'icon'    => 'heroicon-o-arrows-right-left',
                'color'   => file_exists(base_path('bootstrap/cache/routes-v7.php')) ? 'text-green-600' : 'text-gray-400',
                'bg'      => file_exists(base_path('bootstrap/cache/routes-v7.php')) ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-800',
            ],
            [
                'label'   => 'Compiled Views',
                'value'   => count(glob(storage_path('framework/views/*.php'))) . ' files',
                'icon'    => 'heroicon-o-eye',
                'color'   => 'text-purple-600',
                'bg'      => 'bg-purple-50 dark:bg-purple-900/20',
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('clear_config')
                    ->label('Clear Config Cache')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->action(function () {
                        Artisan::call('config:clear');
                        Notification::make()->title('Config cache cleared')->success()->send();
                    }),

                Action::make('clear_routes')
                    ->label('Clear Route Cache')
                    ->icon('heroicon-o-arrows-right-left')
                    ->action(function () {
                        Artisan::call('route:clear');
                        Notification::make()->title('Route cache cleared')->success()->send();
                    }),

                Action::make('clear_views')
                    ->label('Clear View Cache')
                    ->icon('heroicon-o-eye')
                    ->action(function () {
                        Artisan::call('view:clear');
                        Notification::make()->title('View cache cleared')->success()->send();
                    }),

                Action::make('clear_app')
                    ->label('Clear Application Cache')
                    ->icon('heroicon-o-server')
                    ->action(function () {
                        Artisan::call('cache:clear');
                        Notification::make()->title('Application cache cleared')->success()->send();
                    }),
            ])
                ->label('Clear Individual')
                ->icon('heroicon-o-chevron-down')
                ->color('gray'),

            Action::make('clear_all')
                ->label('Clear All Cache')
                ->icon('heroicon-o-fire')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clear All Cache?')
                ->modalDescription('This clears config, route, view, and application cache.')
                ->action(function () {
                    Artisan::call('config:clear');
                    Artisan::call('route:clear');
                    Artisan::call('view:clear');
                    Artisan::call('cache:clear');
                    Notification::make()->title('All cache cleared successfully')->success()->send();
                }),
        ];
    }
}
