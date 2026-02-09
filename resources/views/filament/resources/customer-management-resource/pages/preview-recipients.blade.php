<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Campaign Preview</x-slot>
            <x-slot name="description">
                Type: {{ \App\Models\BulkSMSCampaign::getTypes()[$record->type] ?? $record->type }}
            </x-slot>
            
            <div class="prose dark:prose-invert max-w-none">
                <p><strong>Message Template:</strong></p>
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    {{ $record->message_template }}
                </div>
            </div>
        </x-filament::section>
        
        <x-filament::section>
            <x-slot name="heading">Recipients ({{ count($recipients) }})</x-slot>
            <x-slot name="description">
                The following users will receive this SMS campaign
            </x-slot>
            
            @if(count($recipients) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Phone</th>
                                <th class="px-4 py-3">Reference</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($recipients, 0, 50) as $recipient)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3">{{ $recipient['name'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $recipient['phone'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $recipient['reference'] ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700">
                                            {{ $recipient['status'] ?? 'Unknown' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(count($recipients) > 50)
                    <p class="text-sm text-gray-500 mt-4">Showing first 50 of {{ count($recipients) }} recipients</p>
                @endif
            @else
                <p class="text-gray-500">No recipients found for this campaign type.</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
