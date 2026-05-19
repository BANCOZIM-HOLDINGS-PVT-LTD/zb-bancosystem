<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class QueueMonitor extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Queue Monitor';
    protected static ?string $title           = 'Queue Monitor';
    protected static ?string $navigationGroup = 'System';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.pages.queue-monitor';

    public function getStats(): array
    {
        $pending = 0;
        $failed  = 0;

        try { $pending = DB::table('jobs')->count(); } catch (\Exception) {}
        try { $failed  = DB::table('failed_jobs')->count(); } catch (\Exception) {}

        return [
            [
                'label' => 'Pending Jobs',
                'value' => $pending,
                'icon'  => 'heroicon-o-clock',
                'color' => 'text-yellow-600',
                'bg'    => 'bg-yellow-50 dark:bg-yellow-900/20',
            ],
            [
                'label' => 'Failed Jobs',
                'value' => $failed,
                'icon'  => 'heroicon-o-x-circle',
                'color' => $failed > 0 ? 'text-red-600' : 'text-gray-400',
                'bg'    => $failed > 0 ? 'bg-red-50 dark:bg-red-900/20' : 'bg-gray-50 dark:bg-gray-800',
            ],
            [
                'label' => 'Queue Driver',
                'value' => strtoupper(config('queue.default', 'sync')),
                'icon'  => 'heroicon-o-circle-stack',
                'color' => 'text-blue-600',
                'bg'    => 'bg-blue-50 dark:bg-blue-900/20',
            ],
        ];
    }

    public function getFailedJobs(): \Illuminate\Support\Collection
    {
        try {
            return DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(50)
                ->get()
                ->map(function ($job) {
                    $payload = json_decode($job->payload, true);
                    return [
                        'id'         => $job->id,
                        'uuid'       => $job->uuid,
                        'queue'      => $job->queue,
                        'job_name'   => $payload['displayName'] ?? class_basename($payload['job'] ?? 'Unknown'),
                        'error'      => mb_substr(explode("\n", $job->exception)[0], 0, 120),
                        'failed_at'  => $job->failed_at,
                    ];
                });
        } catch (\Exception) {
            return collect();
        }
    }

    public function retryJob(string $uuid): void
    {
        try {
            Artisan::call('queue:retry', ['id' => [$uuid]]);
            Notification::make()->title('Job queued for retry')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Retry failed: ' . $e->getMessage())->danger()->send();
        }
    }

    public function deleteJob(int $id): void
    {
        try {
            DB::table('failed_jobs')->where('id', $id)->delete();
            Notification::make()->title('Failed job deleted')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Delete failed: ' . $e->getMessage())->danger()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retry_all')
                ->label('Retry All Failed')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('All failed jobs will be pushed back onto the queue.')
                ->action(function () {
                    Artisan::call('queue:retry', ['id' => ['all']]);
                    Notification::make()->title('All failed jobs queued for retry')->success()->send();
                }),

            Action::make('flush_failed')
                ->label('Flush All Failed')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete All Failed Jobs?')
                ->modalDescription('This permanently removes all failed job records.')
                ->action(function () {
                    Artisan::call('queue:flush');
                    Notification::make()->title('All failed jobs deleted')->success()->send();
                }),
        ];
    }
}
