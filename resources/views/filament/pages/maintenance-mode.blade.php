<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Status Banner --}}
        @if($isDown)
            <div class="rounded-xl border border-orange-300 bg-orange-50 dark:bg-orange-900/20 dark:border-orange-700 p-6 flex items-center gap-5">
                <div class="w-14 h-14 rounded-full bg-orange-100 dark:bg-orange-800/40 flex items-center justify-center flex-shrink-0">
                    <x-heroicon-o-wrench-screwdriver class="w-7 h-7 text-orange-600 dark:text-orange-400" />
                </div>
                <div>
                    <p class="text-lg font-bold text-orange-800 dark:text-orange-300">Application is in Maintenance Mode</p>
                    <p class="text-sm text-orange-700 dark:text-orange-400 mt-1">
                        Regular users will see a maintenance page. The admin panel is still accessible.
                        Use the button above to bring the application back online.
                    </p>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-green-300 bg-green-50 dark:bg-green-900/20 dark:border-green-700 p-6 flex items-center gap-5">
                <div class="w-14 h-14 rounded-full bg-green-100 dark:bg-green-800/40 flex items-center justify-center flex-shrink-0">
                    <x-heroicon-o-check-circle class="w-7 h-7 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-lg font-bold text-green-800 dark:text-green-300">Application is Online</p>
                    <p class="text-sm text-green-700 dark:text-green-400 mt-1">
                        All users can access the application normally.
                        Use the button above to put the application into maintenance mode.
                    </p>
                </div>
            </div>
        @endif

        {{-- Info Panel --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">What happens in Maintenance Mode?</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-x-circle class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Public Access Blocked</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">All frontend routes return a 503 maintenance page to regular users.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Admin Panel Stays Up</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">The /admin panel remains accessible so you can continue working.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <x-heroicon-o-arrow-path class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Auto Retry</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Browsers are told to retry after 60 seconds via the Retry-After header.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <x-heroicon-o-clock class="w-5 h-5 text-purple-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">Queue Workers Pause</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Background job processing stops until the application is back online.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
