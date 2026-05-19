<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Status Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($this->getCacheInfo() as $item)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $item['bg'] }} flex-shrink-0">
                            <x-dynamic-component :component="$item['icon']" class="w-5 h-5 {{ $item['color'] }}" />
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $item['label'] }}</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ $item['value'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Clear Cache Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Clear Cache</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                Use the buttons in the top-right to clear individual caches or all at once.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600">
                    <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Config Cache</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Clears the compiled configuration file. Required after changing .env or config files.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600">
                    <x-heroicon-o-arrows-right-left class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Route Cache</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Clears the compiled route file. Required after adding or modifying routes.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600">
                    <x-heroicon-o-eye class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">View Cache</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Clears compiled Blade templates. Required after modifying .blade.php files.</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-4 rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600">
                    <x-heroicon-o-server class="w-5 h-5 text-gray-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Application Cache</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Clears the general application cache store (file/redis/memcached).</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
