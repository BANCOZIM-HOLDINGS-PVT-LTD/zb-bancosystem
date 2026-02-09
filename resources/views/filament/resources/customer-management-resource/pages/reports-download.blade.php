<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">📊 Customer Reports</x-slot>
            <x-slot name="description">
                Download CSV reports for customer outreach and CRM activities
            </x-slot>
        </x-filament::section>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Installments Due Report -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-banknotes class="w-5 h-5 text-warning-500" />
                        Installments Due
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Users with delivered products who have installment payments due.
                    </p>
                    
                    <div class="p-4 bg-warning-50 dark:bg-warning-950 rounded-lg">
                        <span class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                            {{ $this->getInstallmentsDueCount() }}
                        </span>
                        <span class="text-sm text-gray-500 ml-2">users</span>
                    </div>
                    
                    <x-filament::button 
                        wire:click="downloadInstallmentsDue"
                        icon="heroicon-o-arrow-down-tray"
                        color="warning"
                        class="w-full"
                    >
                        Download CSV
                    </x-filament::button>
                </div>
            </x-filament::section>
            
            <!-- Incomplete Applications Report -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-5 h-5 text-info-500" />
                        Incomplete Applications
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Users who started an application but haven't completed it.
                    </p>
                    
                    <div class="p-4 bg-info-50 dark:bg-info-950 rounded-lg">
                        <span class="text-2xl font-bold text-info-600 dark:text-info-400">
                            {{ $this->getIncompleteApplicationsCount() }}
                        </span>
                        <span class="text-sm text-gray-500 ml-2">users</span>
                    </div>
                    
                    <x-filament::button 
                        wire:click="downloadIncompleteApplications"
                        icon="heroicon-o-arrow-down-tray"
                        color="info"
                        class="w-full"
                    >
                        Download CSV
                    </x-filament::button>
                </div>
            </x-filament::section>
            
            <!-- All Registered Users Report -->
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-users class="w-5 h-5 text-success-500" />
                        Registered Users
                    </div>
                </x-slot>
                
                <div class="space-y-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        All users who have registered with phone numbers.
                    </p>
                    
                    <div class="p-4 bg-success-50 dark:bg-success-950 rounded-lg">
                        <span class="text-2xl font-bold text-success-600 dark:text-success-400">
                            {{ $this->getRegisteredUsersCount() }}
                        </span>
                        <span class="text-sm text-gray-500 ml-2">users</span>
                    </div>
                    
                    <x-filament::button 
                        wire:click="downloadRegisteredUsers"
                        icon="heroicon-o-arrow-down-tray"
                        color="success"
                        class="w-full"
                    >
                        Download CSV
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
