<div class="p-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">ID Front</h4>
            @if($record->id_front_url)
                <a href="{{ $record->id_front_url }}" target="_blank">
                    <img 
                        src="{{ $record->id_front_url }}" 
                        alt="ID Front" 
                        class="w-full rounded-lg shadow-md hover:shadow-lg transition-shadow"
                        style="max-height: 300px; object-fit: contain;"
                    >
                </a>
                <p class="mt-2 text-xs text-gray-500">Click to open in new tab</p>
            @else
                <div class="flex items-center justify-center h-48 bg-gray-200 dark:bg-gray-700 rounded-lg">
                    <span class="text-gray-400">No image uploaded</span>
                </div>
            @endif
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">ID Back</h4>
            @if($record->id_back_url)
                <a href="{{ $record->id_back_url }}" target="_blank">
                    <img 
                        src="{{ $record->id_back_url }}" 
                        alt="ID Back" 
                        class="w-full rounded-lg shadow-md hover:shadow-lg transition-shadow"
                        style="max-height: 300px; object-fit: contain;"
                    >
                </a>
                <p class="mt-2 text-xs text-gray-500">Click to open in new tab</p>
            @else
                <div class="flex items-center justify-center h-48 bg-gray-200 dark:bg-gray-700 rounded-lg">
                    <span class="text-gray-400">No image uploaded</span>
                </div>
            @endif
        </div>
    </div>
    
    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <h4 class="text-sm font-medium text-blue-700 dark:text-blue-300 mb-2">Applicant Details</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Name:</span>
                <span class="ml-2 font-medium">{{ $record->first_name }} {{ $record->surname }}</span>
            </div>
            <div>
                <span class="text-gray-500">Province:</span>
                <span class="ml-2 font-medium">{{ $record->province }}</span>
            </div>
            <div>
                <span class="text-gray-500">WhatsApp:</span>
                <span class="ml-2 font-medium">{{ $record->whatsapp_contact }}</span>
            </div>
            <div>
                <span class="text-gray-500">Applied:</span>
                <span class="ml-2 font-medium">{{ $record->created_at->format('M d, Y H:i') }}</span>
            </div>
        </div>
    </div>
</div>
