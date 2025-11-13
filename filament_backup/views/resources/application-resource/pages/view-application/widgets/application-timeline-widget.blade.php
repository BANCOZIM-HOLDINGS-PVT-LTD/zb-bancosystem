<x-filament::section>
    <x-slot name="heading">
        Application Timeline
    </x-slot>

    <div class="space-y-4">
        @php
            $transitions = $record->transitions()->orderBy('created_at', 'desc')->get();
        @endphp
        
        @if($transitions->isEmpty())
            <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                No timeline events recorded for this application.
            </div>
        @else
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @foreach($transitions as $transition)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        @php
                                            $iconColors = [
                                                'completed' => 'bg-green-500',
                                                'in_review' => 'bg-yellow-500',
                                                'approved' => 'bg-green-500',
                                                'rejected' => 'bg-red-500',
                                                'pending_documents' => 'bg-yellow-500',
                                                'processing' => 'bg-blue-500',
                                                'form' => 'bg-gray-500',
                                                'product' => 'bg-gray-500',
                                                'business' => 'bg-gray-500',
                                                'language' => 'bg-gray-500',
                                                'intent' => 'bg-gray-500',
                                                'employer' => 'bg-gray-500',
                                            ];
                                            $bgColor = $iconColors[$transition->to_step] ?? 'bg-gray-500';
                                        @endphp
                                        <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-900 {{ $bgColor }}">
                                            @if($transition->to_step === 'approved')
                                                @svg('heroicon-m-check', 'h-5 w-5 text-white')
                                            @elseif($transition->to_step === 'rejected')
                                                @svg('heroicon-m-x-mark', 'h-5 w-5 text-white')
                                            @elseif($transition->to_step === 'in_review')
                                                @svg('heroicon-m-eye', 'h-5 w-5 text-white')
                                            @elseif($transition->to_step === 'pending_documents')
                                                @svg('heroicon-m-document', 'h-5 w-5 text-white')
                                            @elseif($transition->to_step === 'processing')
                                                @svg('heroicon-m-cog', 'h-5 w-5 text-white')
                                            @else
                                                @svg('heroicon-m-arrow-right', 'h-5 w-5 text-white')
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                        <div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                Status changed from 
                                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $transition->from_step)) }}</span> 
                                                to 
                                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $transition->to_step)) }}</span>
                                                
                                                @if($transition->channel === 'admin')
                                                    by administrator
                                                @else
                                                    via {{ ucfirst($transition->channel) }}
                                                @endif
                                            </p>
                                            
                                            @if(!empty($transition->transition_data['notes']))
                                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                    "{{ $transition->transition_data['notes'] }}"
                                                </p>
                                            @endif
                                        </div>
                                        <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                            {{ $transition->created_at->diffForHumans() }}
                                            <div class="text-xs">{{ $transition->created_at->format('M j, Y g:i A') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-filament::section>