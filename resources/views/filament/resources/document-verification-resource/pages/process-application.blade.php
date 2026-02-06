@php
    $documents = $record->form_data['documents'] ?? $record->form_data['documentsByType'] ?? [];
    $uploadedDocs = $documents['uploadedDocuments'] ?? [];
    // Fallback for flat array structure
    if (empty($uploadedDocs) && is_array($documents)) {
        foreach($documents as $key => $doc) {
            if (is_array($doc) && isset($doc['path'])) {
                $doc['name'] = ucfirst(str_replace('_', ' ', $key));
                $uploadedDocs[$key] = $doc;
            }
        }
    }
@endphp

<x-filament::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-full">
        <!-- LEFT: Application Data -->
        <div class="space-y-6">
             <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold mb-4 text-gray-800 border-b pb-2">Application Details</h2>
                {{-- Reuse the existing component but wrap it to ensure it receives data correctly --}}
                <div class="text-sm">
                    @include('filament.forms.components.application-data', ['getState' => fn() => $record->form_data])
                </div>
             </div>
        </div>

        <!-- RIGHT: Documents -->
        <div class="space-y-6">
            <h2 class="text-lg font-bold text-gray-800">Submitted Documents ({{ count($uploadedDocs) }})</h2>
            
            @foreach($uploadedDocs as $key => $doc)
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 @if($this->documentStatuses[$key] === false) border-red-300 ring-2 ring-red-50 @elseif($this->documentStatuses[$key] === true) border-green-300 ring-2 ring-green-50 @endif">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-lg text-gray-900">{{ $doc['name'] ?? 'Document' }}</h3>
                            <span class="text-xs font-mono text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ $doc['type'] ?? 'Unknown' }}</span>
                        </div>
                        
                        <div class="flex space-x-2">
                            <x-filament::button 
                                size="sm" 
                                color="{{ $this->documentStatuses[$key] === true ? 'success' : 'gray' }}"
                                icon="heroicon-m-check"
                                wire:click="markDocument('{{ $key }}', true)"
                            >
                                Valid
                            </x-filament::button>
                            
                            <x-filament::button 
                                size="sm" 
                                color="{{ $this->documentStatuses[$key] === false ? 'danger' : 'gray' }}"
                                icon="heroicon-m-x-mark"
                                wire:click="markDocument('{{ $key }}', false, 'Document is blurry / illegible')"
                            >
                                Invalid
                            </x-filament::button>
                        </div>
                    </div>

                    <!-- Viewer -->
                    <div class="border rounded-lg bg-gray-50 min-h-[300px] flex items-center justify-center overflow-hidden relative group">
                         @php
                            $path = $doc['path'] ?? '';
                            $url = $path ? Storage::url($path) : '#';
                            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                         @endphp
                         
                         @if($path)
                             @if($ext === 'pdf')
                                <iframe src="{{ $url }}" class="w-full h-[500px]" frameborder="0"></iframe>
                             @elseif(in_array($ext, ['jpg', 'jpeg', 'png', 'webp']))
                                <a href="{{ $url }}" target="_blank" class="cursor-zoom-in">
                                    <img src="{{ $url }}" class="max-w-full max-h-[500px] object-contain transition-transform duration-300 group-hover:scale-105" />
                                </a>
                             @else
                                <div class="text-center p-4">
                                    <p class="mb-2 text-gray-500">Preview not available for {{ $ext }}</p>
                                    <a href="{{ $url }}" target="_blank" class="text-primary-600 underline font-medium">Download to view</a>
                                </div>
                             @endif
                         @else
                            <div class="text-gray-400 italic">File path missing</div>
                         @endif
                    </div>
                    
                    <!-- Rejection Reason -->
                    @if($this->documentStatuses[$key] === false)
                        <div class="mt-4 p-4 bg-red-50 rounded-lg border border-red-100 animate-pulse-once">
                            <label class="text-sm font-bold text-red-800 block mb-2">Why is this document invalid?</label>
                            <select 
                                class="block w-full border-red-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm text-gray-700"
                                wire:change="markDocument('{{ $key }}', false, $event.target.value)"
                            >
                                <option value="Document is blurry / illegible">Blurry or Illegible</option>
                                <option value="Document has expired">Document has expired</option>
                                <option value="Details do not match application">Details do not match application</option>
                                <option value="Wrong document type">Wrong document type</option>
                                <option value="Document is incomplete">Document is incomplete/cutoff</option>
                                <option value="Suspicious / Potentially fraudulent">Suspicious / Potentially fraudulent</option>
                            </select>
                        </div>
                    @endif
                </div>
            @endforeach
            
            @if(empty($uploadedDocs))
                <div class="bg-yellow-50 p-6 rounded-xl border border-yellow-200 text-yellow-800 text-center">
                    <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto mb-2 opacity-50"/>
                    <h3 class="font-bold">No Documents Found</h3>
                    <p class="text-sm">This application does not appear to have any uploaded documents.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament::page>
