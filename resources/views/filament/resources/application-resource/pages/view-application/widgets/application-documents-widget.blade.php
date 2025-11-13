<x-filament::section>
    <x-slot name="heading">
        Application Documents
    </x-slot>

    <div class="space-y-4">
        @php
            $documents = $record->form_data['documents']['uploadedDocuments'] ?? [];
            $selfie = $record->form_data['documents']['selfie'] ?? null;
            $signature = $record->form_data['documents']['signature'] ?? null;
        @endphp
        
        @if(empty($documents) && !$selfie && !$signature)
            <div class="text-center py-4 text-gray-500 dark:text-gray-400">
                No documents uploaded for this application.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @if($selfie)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="p-4">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Applicant Photo</h3>
                            <div class="mt-2">
                                <img src="{{ Storage::disk('public')->url($selfie) }}" alt="Applicant Photo" class="w-full h-auto rounded">
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right">
                            <a href="{{ Storage::disk('public')->url($selfie) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                View Full Size
                            </a>
                        </div>
                    </div>
                @endif
                
                @if($signature)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="p-4">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Applicant Signature</h3>
                            <div class="mt-2">
                                <img src="{{ Storage::disk('public')->url($signature) }}" alt="Applicant Signature" class="w-full h-auto rounded">
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right">
                            <a href="{{ Storage::disk('public')->url($signature) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                View Full Size
                            </a>
                        </div>
                    </div>
                @endif
                
                @foreach($documents as $docType => $docList)
                    @foreach($docList as $index => $document)
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <div class="p-4">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ ucwords(str_replace('_', ' ', $docType)) }} {{ count($docList) > 1 ? ($index + 1) : '' }}
                                </h3>
                                <div class="mt-2">
                                    @php
                                        $extension = pathinfo($document, PATHINFO_EXTENSION);
                                        $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif']);
                                    @endphp
                                    
                                    @if($isImage)
                                        <img src="{{ Storage::disk('public')->url($document) }}" alt="{{ ucwords(str_replace('_', ' ', $docType)) }}" class="w-full h-auto rounded">
                                    @else
                                        <div class="flex items-center justify-center h-32 bg-gray-100 dark:bg-gray-700 rounded">
                                            @svg('heroicon-o-document', 'w-12 h-12 text-gray-400')
                                            <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ strtoupper($extension) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 text-right">
                                <a href="{{ Storage::disk('public')->url($document) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $isImage ? 'View Full Size' : 'Download Document' }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        @endif
    </div>
</x-filament::section>