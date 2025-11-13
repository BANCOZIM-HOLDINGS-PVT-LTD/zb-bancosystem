<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Quick Actions
        </x-slot>
        
        <x-slot name="description">
            Common administrative tasks and system overview
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($this->getViewData()['actions'] as $action)
                <div class="relative">
                    @if(isset($action['url']))
                        <a href="{{ $action['url'] }}" 
                           class="block p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-{{ $action['color'] }}-500 hover:shadow-lg transition-all duration-200">
                    @else
                        <button wire:click="{{ $action['action'] }}" 
                                class="w-full p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-{{ $action['color'] }}-500 hover:shadow-lg transition-all duration-200 text-left">
                    @endif
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    @if($action['icon'] === 'heroicon-o-document-magnifying-glass')
                                        <svg class="w-6 h-6 text-{{ $action['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    @elseif($action['icon'] === 'heroicon-o-cube')
                                        <svg class="w-6 h-6 text-{{ $action['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    @elseif($action['icon'] === 'heroicon-o-users')
                                        <svg class="w-6 h-6 text-{{ $action['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                        </svg>
                                    @elseif($action['icon'] === 'heroicon-o-banknotes')
                                        <svg class="w-6 h-6 text-{{ $action['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @elseif($action['icon'] === 'heroicon-o-heart')
                                        <svg class="w-6 h-6 text-{{ $action['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                    @else
                                        <svg class="w-6 h-6 text-{{ $action['color'] }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $action['label'] }}
                                    </h3>
                                </div>
                            </div>
                            @if($action['badge'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $action['color'] }}-100 text-{{ $action['color'] }}-800 dark:bg-{{ $action['color'] }}-900 dark:text-{{ $action['color'] }}-200">
                                    {{ $action['badge'] }}
                                </span>
                            @endif
                        </div>
                    @if(isset($action['url']))
                        </a>
                    @else
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Quick Stats Summary -->
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">System Overview</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                @php
                    $stats = $this->getViewData()['stats'];
                    $statItems = [
                        ['label' => 'Pending Approvals', 'value' => $stats['pending_approvals'], 'color' => 'warning'],
                        ['label' => 'Urgent Apps', 'value' => $stats['urgent_applications'], 'color' => 'danger'],
                        ['label' => 'Today\'s Apps', 'value' => $stats['today_applications'], 'color' => 'info'],
                        ['label' => 'Pending Commissions', 'value' => $stats['pending_commissions'], 'color' => 'warning'],
                        ['label' => 'Active Agents', 'value' => $stats['active_agents'], 'color' => 'success'],
                        ['label' => 'Total Products', 'value' => $stats['total_products'], 'color' => 'primary'],
                    ];
                @endphp
                
                @foreach($statItems as $stat)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-{{ $stat['color'] }}-600 dark:text-{{ $stat['color'] }}-400">
                            {{ number_format($stat['value']) }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $stat['label'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
