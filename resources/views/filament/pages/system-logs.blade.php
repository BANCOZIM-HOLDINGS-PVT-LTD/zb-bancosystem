<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Toolbar --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Level</label>
                    <select wire:model.live="levelFilter"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="all">All Levels</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="critical">Critical</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                        <option value="emergency">Emergency</option>
                        <option value="alert">Alert</option>
                        <option value="notice">Notice</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-600 dark:text-gray-400">Show</label>
                    <select wire:model.live="lineCount"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                        <option value="50">50 entries</option>
                        <option value="100">100 entries</option>
                        <option value="250">250 entries</option>
                        <option value="500">500 entries</option>
                    </select>
                </div>

                <div class="ml-auto text-sm text-gray-500 dark:text-gray-400">
                    File size: <span class="font-medium">{{ $this->getLogFileSize() }}</span>
                </div>
            </div>
        </div>

        {{-- Log Entries --}}
        @php $logs = $this->getLogs(); @endphp

        @if(empty($logs))
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                <x-heroicon-o-document-text class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                <p class="text-gray-500 dark:text-gray-400">No log entries found.</p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-44">Timestamp</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24">Level</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($logs as $entry)
                                @php
                                    $badge = match($entry['level']) {
                                        'emergency', 'alert', 'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                        'error'                          => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                        'warning'                        => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                        'info', 'notice'                 => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                        default                          => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                    $rowBg = match($entry['level']) {
                                        'emergency', 'alert', 'critical', 'error' => 'bg-red-50/30 dark:bg-red-900/10',
                                        'warning'                                 => 'bg-yellow-50/30 dark:bg-yellow-900/10',
                                        default                                   => '',
                                    };
                                @endphp
                                <tr class="{{ $rowBg }} hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">
                                        {{ $entry['datetime'] }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badge }}">
                                            {{ strtoupper($entry['level']) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300 font-mono text-xs break-all">
                                        {{ $entry['message'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-200 dark:border-gray-600 text-xs text-gray-500 dark:text-gray-400">
                    Showing {{ count($logs) }} most recent entries (newest first)
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
