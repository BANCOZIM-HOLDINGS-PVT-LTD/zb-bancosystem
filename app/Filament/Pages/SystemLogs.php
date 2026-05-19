<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SystemLogs extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'System Logs';
    protected static ?string $title           = 'System Logs';
    protected static ?string $navigationGroup = 'System';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.pages.system-logs';

    public string $levelFilter = 'all';
    public int    $lineCount   = 100;

    // Reactive: re-render when filters change
    public function updatedLevelFilter(): void {}
    public function updatedLineCount(): void {}

    public function getLogs(): array
    {
        $path = storage_path('logs/laravel.log');

        if (!file_exists($path) || filesize($path) === 0) {
            return [];
        }

        $content = $this->readTail($path, 300_000);

        preg_match_all(
            '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+?)(?=\n\[|\z)/s',
            $content,
            $matches,
            PREG_SET_ORDER
        );

        $entries = [];
        foreach (array_reverse($matches) as $m) {
            $level = strtolower($m[3]);

            if ($this->levelFilter !== 'all' && $level !== $this->levelFilter) {
                continue;
            }

            $entries[] = [
                'datetime' => $m[1],
                'env'      => $m[2],
                'level'    => $level,
                'message'  => trim(mb_substr($m[4], 0, 600)),
            ];

            if (count($entries) >= $this->lineCount) {
                break;
            }
        }

        return $entries;
    }

    public function getLogFileSize(): string
    {
        $path = storage_path('logs/laravel.log');
        if (!file_exists($path)) {
            return '0 B';
        }
        $bytes = filesize($path);
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 1) . ' ' . $unit;
            }
            $bytes /= 1024;
        }
        return round($bytes, 1) . ' TB';
    }

    private function readTail(string $path, int $bytes): string
    {
        $size   = filesize($path);
        $handle = fopen($path, 'r');
        fseek($handle, -min($bytes, $size), SEEK_END);
        $content = fread($handle, min($bytes, $size));
        fclose($handle);
        return $content;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_log')
                ->label('Clear Log')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Clear Log File?')
                ->modalDescription('This permanently erases all log entries. This cannot be undone.')
                ->action(function () {
                    file_put_contents(storage_path('logs/laravel.log'), '');
                    Notification::make()->title('Log file cleared')->success()->send();
                }),
        ];
    }
}
