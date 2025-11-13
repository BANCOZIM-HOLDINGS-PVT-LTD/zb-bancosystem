<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
            Application: {{ $record->reference_code ?? $record->session_id }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Current Status: 
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                @if(in_array($record->current_step, ['completed', 'approved'])) bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                @elseif(in_array($record->current_step, ['in_review', 'processing', 'pending_documents'])) bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                @elseif($record->current_step === 'rejected') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                @endif">
                {{ ucfirst(str_replace('_', ' ', $record->current_step)) }}
            </span>
        </p>
    </div>

    @if($transitions->count() > 0)
        <div class="space-y-3">
            <h4 class="text-md font-medium text-gray-900 dark:text-white">Status Change History</h4>
            
            @foreach($transitions as $transition)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-white dark:bg-gray-900">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ ucfirst(str_replace('_', ' ', $transition->from_step)) }}
                                </span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ ucfirst(str_replace('_', ' ', $transition->to_step)) }}
                                </span>
                            </div>
                            
                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <div class="flex items-center space-x-4">
                                    <span><strong>Date:</strong> {{ $transition->created_at->format('M j, Y g:i A') }}</span>
                                    <span><strong>Channel:</strong> {{ ucfirst($transition->channel) }}</span>
                                </div>
                                
                                @if($transition->transition_data)
                                    @php $data = is_array($transition->transition_data) ? $transition->transition_data : json_decode($transition->transition_data, true) @endphp
                                    
                                    @if(isset($data['admin_name']))
                                        <div><strong>Updated by:</strong> {{ $data['admin_name'] }} (ID: {{ $data['admin_id'] ?? 'N/A' }})</div>
                                    @endif
                                    
                                    @if(isset($data['notes']) && !empty($data['notes']))
                                        <div class="mt-2 p-2 bg-gray-50 dark:bg-gray-800 rounded">
                                            <strong>Notes:</strong> {{ $data['notes'] }}
                                        </div>
                                    @endif
                                    
                                    @if(isset($data['bulk_update']) && $data['bulk_update'])
                                        <div class="text-xs text-orange-600 dark:text-orange-400">
                                            <strong>Bulk Update</strong> 
                                            @if(isset($data['bulk_count']))
                                                ({{ $data['bulk_count'] }} applications)
                                            @endif
                                        </div>
                                    @endif
                                    
                                    @if(isset($data['ip_address']))
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            IP: {{ $data['ip_address'] }}
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                        
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $transition->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            <h3 class="mt-2 text-sm font-medium">No status changes recorded</h3>
            <p class="mt-1 text-sm">This application hasn't had any status changes yet.</p>
        </div>
    @endif
</div>