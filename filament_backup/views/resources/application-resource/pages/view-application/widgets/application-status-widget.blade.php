<x-filament::section>
    <x-slot name="heading">
        Application Status
    </x-slot>

    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Status</h3>
                <div class="mt-1 flex items-center">
                    @php
                        $statusColors = [
                            'completed' => 'bg-green-100 text-green-800',
                            'in_review' => 'bg-yellow-100 text-yellow-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'pending_documents' => 'bg-yellow-100 text-yellow-800',
                            'processing' => 'bg-blue-100 text-blue-800',
                        ];
                        $color = $statusColors[$record->current_step] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $color }}">
                        {{ ucfirst(str_replace('_', ' ', $record->current_step)) }}
                    </span>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Reference Code</h3>
                <div class="mt-1 flex items-center">
                    <span class="text-lg font-semibold">{{ $record->reference_code ?? 'N/A' }}</span>
                    @if($record->reference_code)
                        <button 
                            onclick="navigator.clipboard.writeText('{{ $record->reference_code }}'); this.querySelector('span').innerText = 'Copied!'; setTimeout(() => this.querySelector('span').innerText = 'Copy', 2000)"
                            class="ml-2 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            <span>Copy</span>
                        </button>
                    @endif
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Submission Channel</h3>
                <div class="mt-1">
                    @php
                        $channelIcons = [
                            'web' => 'heroicon-o-computer-desktop',
                            'whatsapp' => 'heroicon-o-chat-bubble-left-right',
                            'ussd' => 'heroicon-o-device-phone-mobile',
                            'mobile_app' => 'heroicon-o-device-phone-mobile',
                        ];
                        $icon = $channelIcons[$record->channel] ?? 'heroicon-o-question-mark-circle';
                    @endphp
                    <div class="flex items-center">
                        @svg($icon, 'w-5 h-5 mr-1 text-gray-500')
                        <span class="text-base font-medium">{{ ucfirst($record->channel) }}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Application Progress</h3>
            <div class="mt-3">
                @php
                    $steps = [
                        'submitted' => true,
                        'documents_verified' => in_array($record->current_step, ['in_review', 'approved', 'processing']),
                        'in_review' => in_array($record->current_step, ['in_review', 'approved', 'processing']),
                        'approved' => $record->current_step === 'approved',
                        'processing' => $record->current_step === 'processing',
                    ];
                    
                    $currentStep = array_search($record->current_step, array_keys($steps)) ?: 0;
                @endphp
                
                <div class="relative">
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                        <div style="width:{{ min(100, ($currentStep / (count($steps) - 1)) * 100) }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"></div>
                    </div>
                    <div class="flex justify-between">
                        @foreach($steps as $step => $completed)
                            <div class="text-center">
                                <div class="
                                    w-6 h-6 mx-auto rounded-full flex items-center justify-center
                                    {{ $completed ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }}
                                ">
                                    @if($completed)
                                        @svg('heroicon-o-check', 'w-4 h-4')
                                    @else
                                        <span class="text-xs">{{ $loop->iteration }}</span>
                                    @endif
                                </div>
                                <div class="text-xs mt-1 {{ $completed ? 'text-blue-500 font-medium' : 'text-gray-500' }}">
                                    {{ ucfirst(str_replace('_', ' ', $step)) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament::section>