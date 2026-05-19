<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach($this->getStats() as $stat)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $stat['bg'] }} flex-shrink-0">
                            <x-dynamic-component :component="$stat['icon']" class="w-5 h-5 {{ $stat['color'] }}" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $stat['value'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Failed Jobs Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Failed Jobs</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Most recent 50 failed jobs</p>
            </div>

            @php $failedJobs = $this->getFailedJobs(); @endphp

            @if($failedJobs->isEmpty())
                <div class="p-12 text-center">
                    <x-heroicon-o-check-circle class="w-12 h-12 text-green-400 mx-auto mb-3" />
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No failed jobs</p>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Your queue is healthy.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Queue</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Job</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Error</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Failed At</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($failedJobs as $job)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ $job['id'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $job['queue'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200 font-medium">
                                        {{ $job['job_name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-red-600 dark:text-red-400 font-mono max-w-xs truncate" title="{{ $job['error'] }}">
                                        {{ $job['error'] }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $job['failed_at'] }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button wire:click="retryJob('{{ $job['uuid'] }}')"
                                                    class="text-xs font-medium text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300">
                                                Retry
                                            </button>
                                            <span class="text-gray-300">|</span>
                                            <button wire:click="deleteJob({{ $job['id'] }})"
                                                    wire:confirm="Delete this failed job?"
                                                    class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
