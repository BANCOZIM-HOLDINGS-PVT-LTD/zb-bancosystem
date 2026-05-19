@php
    $documents   = $record->form_data['documents'] ?? [];
    $refs        = $documents['documentReferences'] ?? [];
    $selfiePath  = $documents['selfie'] ?? null;
    $sigPath     = $documents['signature'] ?? null;

    $docLabels = [
        'national_id'           => 'National ID',
        'payslip'               => 'Payslip',
        'passport_photo'        => 'Passport Photo',
        'bank_statement'        => 'Bank Statement',
        'employment_letter'     => 'Employment Letter',
        'business_registration' => 'Business Registration',
        'financial_statements'  => 'Financial Statements',
        'director_id'           => "Director's ID",
    ];

    // Collect all viewable items
    $items = [];
    if ($selfiePath) {
        $items[] = ['label' => 'Selfie', 'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($selfiePath), 'path' => $selfiePath, 'type' => 'image'];
    }
    foreach ($refs as $type => $files) {
        foreach ((array) $files as $file) {
            $path = $file['path'] ?? null;
            if (!$path) continue;
            $mime  = $file['type'] ?? 'image/jpeg';
            $isPdf = str_contains($mime, 'pdf');
            $items[] = [
                'label' => ($docLabels[$type] ?? ucwords(str_replace('_', ' ', $type))) . ' — ' . ($file['name'] ?? basename($path)),
                'url'   => \Illuminate\Support\Facades\Storage::disk('public')->url($path),
                'path'  => $path,
                'type'  => $isPdf ? 'pdf' : 'image',
            ];
        }
    }
@endphp

<div class="p-4 space-y-6">
    @if(count($items) === 0)
        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
            <x-heroicon-o-document-magnifying-glass class="w-12 h-12 mb-3"/>
            <p class="text-sm">No documents found in this application's form data.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @foreach($items as $item)
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2 truncate" title="{{ $item['label'] }}">
                        {{ $item['label'] }}
                    </p>

                    @if($item['type'] === 'pdf')
                        <div class="flex flex-col items-center justify-center h-40 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-700">
                            <x-heroicon-o-document-text class="w-12 h-12 text-red-400 mb-2"/>
                            <a href="{{ $item['url'] }}" target="_blank"
                               class="text-sm text-blue-600 hover:underline font-medium flex items-center gap-1">
                                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4"/>
                                Open PDF
                            </a>
                        </div>
                    @else
                        <a href="{{ $item['url'] }}" target="_blank" class="block group">
                            <img
                                src="{{ $item['url'] }}"
                                alt="{{ $item['label'] }}"
                                class="w-full rounded border border-gray-200 dark:border-gray-600 group-hover:shadow-lg transition-shadow"
                                style="max-height:280px; object-fit:contain; background:#f9fafb;"
                                onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                            >
                            <div style="display:none;" class="flex-col items-center justify-center h-36 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-700">
                                <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-red-400 mb-1"/>
                                <span class="text-xs text-red-500">Could not load image</span>
                                <a href="{{ $item['url'] }}" target="_blank" class="mt-1 text-xs text-blue-500 underline">Try direct link</a>
                            </div>
                        </a>
                        <p class="mt-1 text-xs text-gray-400 flex items-center gap-1">
                            <x-heroicon-o-arrow-top-right-on-square class="w-3 h-3"/>
                            Click to open full-size
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Signature --}}
    @if($sigPath)
        <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-2">Digital Signature</p>
            <img
                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($sigPath) }}"
                alt="Signature"
                class="max-h-24 rounded border border-gray-200 bg-white"
                style="object-fit:contain;"
            >
        </div>
    @endif
</div>
