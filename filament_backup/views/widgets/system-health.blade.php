<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            System Health Monitor
        </x-slot>

        <div class="space-y-4">
            <!-- Overall Status -->
            <div class="flex items-center justify-between p-4 rounded-lg {{ $overallStatus === 'healthy' ? 'bg-green-50 border border-green-200' : ($overallStatus === 'warning' ? 'bg-yellow-50 border border-yellow-200' : 'bg-red-50 border border-red-200') }}">
                <div class="flex items-center space-x-3">
                    @if($overallStatus === 'healthy')
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @elseif($overallStatus === 'warning')
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    @else
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    @endif
                    <div>
                        <h3 class="text-lg font-semibold {{ $overallStatus === 'healthy' ? 'text-green-800' : ($overallStatus === 'warning' ? 'text-yellow-800' : 'text-red-800') }}">
                            System Status: {{ ucfirst($overallStatus) }}
                        </h3>
                        <p class="text-sm {{ $overallStatus === 'healthy' ? 'text-green-600' : ($overallStatus === 'warning' ? 'text-yellow-600' : 'text-red-600') }}">
                            Last checked: {{ $healthMetrics['timestamp'] ?? 'Unknown' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Service Status Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach(['database', 'cache', 'storage', 'queue'] as $service)
                    @php
                        $serviceData = $healthMetrics[$service] ?? ['status' => 'unknown'];
                        $status = $serviceData['status'] ?? 'unknown';
                        $statusColor = match($status) {
                            'healthy' => 'green',
                            'degraded', 'warning' => 'yellow',
                            'unhealthy' => 'red',
                            default => 'gray'
                        };
                    @endphp
                    
                    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">{{ ucfirst($service) }}</h4>
                                <div class="flex items-center mt-1">
                                    <div class="w-2 h-2 rounded-full bg-{{ $statusColor }}-500 mr-2"></div>
                                    <span class="text-xs text-{{ $statusColor }}-600 font-medium">{{ ucfirst($status) }}</span>
                                </div>
                            </div>
                            
                            @if($status === 'healthy')
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @elseif($status === 'warning' || $status === 'degraded')
                                <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            @endif
                        </div>
                        
                        @if(isset($serviceData['response_time_ms']))
                            <div class="mt-2">
                                <span class="text-xs text-gray-500">Response: {{ number_format($serviceData['response_time_ms'], 1) }}ms</span>
                            </div>
                        @endif
                        
                        @if(isset($serviceData['error']))
                            <div class="mt-2">
                                <span class="text-xs text-red-600">{{ $serviceData['error'] }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Memory and Disk Usage -->
            @if(isset($healthMetrics['memory']) || isset($healthMetrics['disk']))
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if(isset($healthMetrics['memory']))
                        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Memory Usage</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs">
                                    <span>Used: {{ $healthMetrics['memory']['current_usage_mb'] ?? 0 }}MB</span>
                                    <span>{{ $healthMetrics['memory']['usage_percentage'] ?? 0 }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-{{ $healthMetrics['memory']['usage_percentage'] > 90 ? 'red' : ($healthMetrics['memory']['usage_percentage'] > 75 ? 'yellow' : 'green') }}-500 h-2 rounded-full" 
                                         style="width: {{ $healthMetrics['memory']['usage_percentage'] ?? 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(isset($healthMetrics['disk']))
                        <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Disk Usage</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs">
                                    <span>Used: {{ $healthMetrics['disk']['used_gb'] ?? 0 }}GB</span>
                                    <span>{{ $healthMetrics['disk']['usage_percentage'] ?? 0 }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-{{ $healthMetrics['disk']['usage_percentage'] > 95 ? 'red' : ($healthMetrics['disk']['usage_percentage'] > 85 ? 'yellow' : 'green') }}-500 h-2 rounded-full" 
                                         style="width: {{ $healthMetrics['disk']['usage_percentage'] ?? 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Active Alerts -->
            @if(!empty($alerts))
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <h4 class="text-sm font-medium text-red-800 mb-2">Active Alerts</h4>
                    <div class="space-y-2">
                        @foreach($alerts as $alert)
                            <div class="flex items-start space-x-2">
                                <svg class="w-4 h-4 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <span class="text-sm text-red-800 font-medium">{{ $alert['service'] ?? 'System' }}</span>
                                    <p class="text-xs text-red-600">{{ $alert['message'] ?? 'Unknown alert' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
