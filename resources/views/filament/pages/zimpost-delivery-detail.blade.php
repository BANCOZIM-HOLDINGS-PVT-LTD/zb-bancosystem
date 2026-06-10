<x-filament-panels::page>
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
    @elseif (!empty($delivery))
        @php
            $statusColors = [
                'pending' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                'assigned' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                'picked_up' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
                'in_transit' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200',
                'delivered' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            ];
            $statusKey = $delivery['status'] ?? '';
            $statusCls = $statusColors[$statusKey] ?? 'bg-gray-100 text-gray-800';
            $driver = $delivery['driver'] ?? null;
            $events = $delivery['tracking_events'] ?? ($delivery['events'] ?? []);
        @endphp

        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Tracking Number</p>
                        <p class="text-xl font-mono font-semibold text-gray-900 dark:text-gray-100">{{ $delivery['tracking_number'] ?? '—' }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Reference: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $delivery['reference'] ?? '—' }}</span>
                        </p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusCls }}">
                        {{ ucwords(str_replace('_', ' ', $statusKey)) }}
                    </span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="bg-gray-50 dark:bg-gray-900/30 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Amount</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ isset($delivery['amount_usd']) ? '$' . number_format((float) $delivery['amount_usd'], 2) : '—' }}
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/30 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Distance / Duration</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $delivery['distance_km'] ?? '—' }} km
                            @if (!empty($delivery['duration_minutes']))
                                · {{ round($delivery['duration_minutes']) }} min
                            @endif
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/30 rounded-lg p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Vehicle</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $delivery['vehicle_type'] ?? '—' }}</p>
                    </div>
                </div>

                @if ($localApplication)
                    <div class="mt-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                        <p class="text-xs uppercase text-emerald-700 dark:text-emerald-300">Linked local application</p>
                        <p class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            {{ $localApplication->reference_code }}
                        </p>
                        @if ($localTracking)
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                Local status: <span class="font-medium">{{ ucwords(str_replace('_', ' ', $localTracking->status)) }}</span>
                                @if ($localTracking->zimpost_last_synced_at)
                                    · Last synced {{ $localTracking->zimpost_last_synced_at->diffForHumans() }}
                                @endif
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            @if ($driver)
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-gray-100">Driver</h3>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Name</p>
                            <p class="text-base font-medium text-gray-900 dark:text-gray-100">{{ $driver['name'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Phone</p>
                            <p class="text-base font-medium text-gray-900 dark:text-gray-100">
                                @if (!empty($driver['phone']))
                                    <a href="tel:{{ $driver['phone'] }}" class="text-amber-600 hover:underline">{{ $driver['phone'] }}</a>
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Vehicle reg</p>
                            <p class="text-base font-mono text-gray-900 dark:text-gray-100">{{ $driver['vehicle_registration'] ?? ($driver['vehicle_reg'] ?? '—') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if (!empty($events))
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Tracking events</h3>
                    <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-3 space-y-5">
                        @foreach ($events as $evt)
                            <li class="ml-4">
                                <span class="absolute -left-1.5 flex h-3 w-3 rounded-full bg-amber-500"></span>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ ucwords(str_replace('_', ' ', $evt['status'] ?? ($evt['event'] ?? 'Update'))) }}
                                </p>
                                @php
                                    $at = $evt['at'] ?? ($evt['timestamp'] ?? ($evt['created_at'] ?? null));
                                @endphp
                                @if ($at)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($at)->format('Y-m-d H:i') }}</p>
                                @endif
                                @if (!empty($evt['note']) || !empty($evt['description']))
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $evt['note'] ?? $evt['description'] }}</p>
                                @endif
                                @if (!empty($evt['location']['address']))
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">📍 {{ $evt['location']['address'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    @else
        <div class="text-center text-gray-500 dark:text-gray-400 py-10">Loading delivery…</div>
    @endif
</x-filament-panels::page>
