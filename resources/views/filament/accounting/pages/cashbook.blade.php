<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <x-filament::section>
            <div class="text-xl font-bold text-success-600">Total Cash In</div>
            <div class="text-2xl">${{ number_format($total_in, 2) }}</div>
        </x-filament::section>
        
        <x-filament::section>
            <div class="text-xl font-bold text-danger-600">Total Cash Out</div>
            <div class="text-2xl">${{ number_format($total_out, 2) }}</div>
        </x-filament::section>
        
        <x-filament::section>
            <div class="text-xl font-bold {{ $net_balance >= 0 ? 'text-success-600' : 'text-danger-600' }}">Net Cash Position</div>
            <div class="text-2xl">${{ number_format($net_balance, 2) }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Description</th>
                        <th class="px-6 py-3 text-right">Debit (Out)</th>
                        <th class="px-6 py-3 text-right">Credit (In)</th>
                        <th class="px-6 py-3 text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ledger as $entry)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                            <td class="px-6 py-4">{{ \Carbon\Carbon::parse($entry['date'])->format('Y-m-d') }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $entry['description'] }}</td>
                            <td class="px-6 py-4 text-right text-danger-600">
                                @if($entry['debit'] > 0)
                                    ${{ number_format($entry['debit'], 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-success-600">
                                @if($entry['credit'] > 0)
                                    ${{ number_format($entry['credit'], 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-bold">
                                ${{ number_format($entry['balance'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
