<x-filament-panels::page>
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @php
                $stats = $this->getPayDayStats();
            @endphp
            @foreach ($stats as $stat)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stat['count'] }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $stat['bg'] }}">
                            <x-heroicon-o-calendar class="w-5 h-5 {{ $stat['color'] }}" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
