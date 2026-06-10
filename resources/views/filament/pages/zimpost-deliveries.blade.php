<x-filament-panels::page>
    <div class="space-y-4">
        @if (!empty($error))
            <div class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-4">
                <p class="font-semibold text-red-800 dark:text-red-200">ZimPost API error</p>
                <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ $error['message'] ?? '' }}</p>
                @if (!empty($error['code']))
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1 font-mono">Code: {{ $error['code'] }}</p>
                @endif
                @if (!empty($error['hint']))
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">Hint: {{ $error['hint'] }}</p>
                @endif
            </div>
        @endif

        <form method="GET" action="" class="flex flex-wrap items-end gap-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Search by reference</label>
                <input type="text" name="search" value="{{ $currentSearch }}" placeholder="e.g. ZB-2026-0001"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm" />
            </div>
            <div class="min-w-[180px]">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected($currentStatus === $s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 text-sm font-medium">
                Filter
            </button>
            @if ($currentSearch || $currentStatus)
                <a href="{{ url()->current() }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">Clear</a>
            @endif
        </form>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Tracking #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Amount (USD)</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Distance</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Vehicle</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Created</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($paginator as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                                <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $row['tracking_number'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $row['reference'] ?? '—' }}</span>
                                    @if (!empty($row['local_application']))
                                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200" title="Matched local application">Linked</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                            'assigned' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                            'picked_up' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
                                            'in_transit' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200',
                                            'delivered' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                        ];
                                        $cls = $statusColors[$row['status'] ?? ''] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $cls }}">
                                        {{ ucwords(str_replace('_', ' ', $row['status'] ?? '—')) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-gray-100">
                                    {{ $row['amount_usd'] !== null ? '$' . number_format((float) $row['amount_usd'], 2) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-700 dark:text-gray-300">
                                    {{ $row['distance_km'] !== null ? $row['distance_km'] . ' km' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $row['vehicle_type'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $row['created_at'] ? \Illuminate\Support\Carbon::parse($row['created_at'])->format('Y-m-d H:i') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    @if ($row['id'])
                                        <a href="{{ route($detailRouteName) }}?id={{ urlencode($row['id']) }}"
                                            class="inline-flex items-center px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium">
                                            View
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No deliveries found for this filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $paginator->links() }}
            </div>
        </div>
    </div>
</x-filament-panels::page>
