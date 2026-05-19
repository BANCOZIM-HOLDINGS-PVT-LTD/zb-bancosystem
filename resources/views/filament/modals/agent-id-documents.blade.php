<div class="p-4 space-y-6">
    {{-- ID Images --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- ID Front --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                <x-heroicon-o-identification class="w-4 h-4 text-blue-500"/>
                ID Front
            </h4>
            @if($record->id_front_url)
                @php $proxyFront = route('admin.agent.media', ['agent' => $record->id, 'side' => 'front']); @endphp
                <a href="{{ $proxyFront }}" target="_blank" class="block group">
                    <img
                        src="{{ $proxyFront }}"
                        alt="ID Front"
                        class="w-full rounded-lg shadow-md group-hover:shadow-xl transition-shadow border border-gray-200 dark:border-gray-600"
                        style="min-height:180px; max-height:360px; object-fit:contain; background:#f3f4f6;"
                        onerror="this.onerror=null; this.src=''; this.parentElement.innerHTML='<div class=\'flex flex-col items-center justify-center h-48 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200\'><svg xmlns=\'http://www.w3.org/2000/svg\' class=\'h-10 w-10 text-red-400 mb-2\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z\'/></svg><span class=\'text-sm text-red-500\'>Image could not be loaded</span><a href=\'{{ $record->id_front_url }}\' target=\'_blank\' class=\'mt-1 text-xs text-blue-500 underline\'>Try direct link</a></div>';"
                    >
                </a>
                <p class="mt-2 text-xs text-gray-400 flex items-center gap-1">
                    <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3"/>
                    Click image to open full-size in new tab
                </p>
            @else
                <div class="flex flex-col items-center justify-center h-48 bg-gray-100 dark:bg-gray-700 rounded-lg border border-dashed border-gray-300 dark:border-gray-500">
                    <x-heroicon-o-photo class="w-10 h-10 text-gray-300 mb-2"/>
                    <span class="text-sm text-gray-400">No image uploaded</span>
                </div>
            @endif
        </div>

        {{-- ID Back --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center gap-2">
                <x-heroicon-o-identification class="w-4 h-4 text-blue-500"/>
                ID Back
            </h4>
            @if($record->id_back_url)
                @php $proxyBack = route('admin.agent.media', ['agent' => $record->id, 'side' => 'back']); @endphp
                <a href="{{ $proxyBack }}" target="_blank" class="block group">
                    <img
                        src="{{ $proxyBack }}"
                        alt="ID Back"
                        class="w-full rounded-lg shadow-md group-hover:shadow-xl transition-shadow border border-gray-200 dark:border-gray-600"
                        style="min-height:180px; max-height:360px; object-fit:contain; background:#f3f4f6;"
                        onerror="this.onerror=null; this.src=''; this.parentElement.innerHTML='<div class=\'flex flex-col items-center justify-center h-48 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200\'><svg xmlns=\'http://www.w3.org/2000/svg\' class=\'h-10 w-10 text-red-400 mb-2\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z\'/></svg><span class=\'text-sm text-red-500\'>Image could not be loaded</span></div>';"
                    >
                </a>
                <p class="mt-2 text-xs text-gray-400 flex items-center gap-1">
                    <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3"/>
                    Click image to open full-size in new tab
                </p>
            @else
                <div class="flex flex-col items-center justify-center h-48 bg-gray-100 dark:bg-gray-700 rounded-lg border border-dashed border-gray-300 dark:border-gray-500">
                    <x-heroicon-o-photo class="w-10 h-10 text-gray-300 mb-2"/>
                    <span class="text-sm text-gray-400">No image uploaded</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Applicant Details --}}
    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
        <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-3 flex items-center gap-2">
            <x-heroicon-o-user class="w-4 h-4"/>
            Applicant Details
        </h4>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Name:</span>
                <span class="ml-2 font-medium text-gray-800 dark:text-gray-100">{{ $record->first_name }} {{ $record->surname }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Province:</span>
                <span class="ml-2 font-medium text-gray-800 dark:text-gray-100">{{ $record->province }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">WhatsApp:</span>
                <span class="ml-2 font-medium text-gray-800 dark:text-gray-100">{{ $record->whatsapp_contact }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">National ID:</span>
                <span class="ml-2 font-medium text-gray-800 dark:text-gray-100">{{ $record->id_number ?: '—' }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Applied:</span>
                <span class="ml-2 font-medium text-gray-800 dark:text-gray-100">{{ $record->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Status:</span>
                <span class="ml-2 font-medium {{ $record->status === 'approved' ? 'text-green-600' : ($record->status === 'rejected' ? 'text-red-600' : 'text-yellow-600') }}">
                    {{ ucfirst($record->status) }}
                </span>
            </div>
        </div>
    </div>
</div>
