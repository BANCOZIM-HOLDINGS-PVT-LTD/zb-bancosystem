@if(empty($documents))
    <div class="p-4 text-center text-gray-500">
        No documents uploaded.
    </div>
@else
    <div class="space-y-4">
        @if(isset($documents['uploadedDocuments']))
            {{-- Format: array of objects { name, type, path, ... } --}}
            @foreach($documents['uploadedDocuments'] as $doc)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <div class="font-medium">{{ $doc['name'] ?? 'Document' }}</div>
                        <div class="text-xs text-gray-500">{{ $doc['type'] ?? 'Unknown Type' }}</div>
                    </div>
                    <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="text-primary-600 hover:underline text-sm font-medium">
                        View / Download
                    </a>
                </div>
            @endforeach
        @elseif(is_array($documents))
             {{-- Fallback for other structures --}}
             @foreach($documents as $key => $doc)
                 @if(is_array($doc) && isset($doc['path']))
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <div class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                        </div>
                        <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="text-primary-600 hover:underline text-sm font-medium">
                            View / Download
                        </a>
                    </div>
                 @endif
             @endforeach
        @endif
    </div>
@endif
