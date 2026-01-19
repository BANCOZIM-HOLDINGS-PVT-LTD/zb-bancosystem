<?php

namespace App\Filament\Hr\Widgets;

use App\Models\User;
use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\DailyRegister;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HRStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Employee counts (excluding admins)
        $employeeCount = User::whereIn('role', ['employee', 'ROLE_HR', 'ROLE_ACCOUNTING', 'ROLE_STORES'])->count();
        
        // Intern count
        $internCount = User::where('role', 'intern')->count();
        
        // Agent counts
        $onlineAgentCount = Agent::where('agent_type', 'online')->where('status', 'active')->count();
        $physicalAgentCount = Agent::where('agent_type', 'physical')->where('status', 'active')->count();
        
        // Pending agent applications
        $pendingAgentApplications = AgentApplication::where('status', 'pending')->count();
        
        // Today's attendance
        $todayPresent = DailyRegister::today()->where('status', 'present')->count();
        $todayAbsent = DailyRegister::today()->where('status', 'absent')->count();
        $todayLate = DailyRegister::today()->where('status', 'late')->count();

        return [
            Stat::make('Employees', $employeeCount)
                ->description('Active employees')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Interns', $internCount)
                ->description('Current interns')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Online Agents', $onlineAgentCount)
                ->description('Active online agents')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('primary'),

            Stat::make('Physical Agents', $physicalAgentCount)
                ->description('Active physical agents')
                ->descriptionIcon('heroicon-m-user')
                ->color('warning'),

            Stat::make('Pending Applications', $pendingAgentApplications)
                ->description('Agent applications awaiting review')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingAgentApplications > 0 ? 'danger' : 'success'),

            Stat::make("Today's Attendance", "{$todayPresent} present")
                ->description($todayLate > 0 ? "{$todayLate} late, {$todayAbsent} absent" : "{$todayAbsent} absent")
                ->descriptionIcon('heroicon-m-calendar')
                ->color($todayAbsent > 0 ? 'warning' : 'success'),
        ];
    }
}
