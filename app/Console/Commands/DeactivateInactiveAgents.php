<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\AgentActivityLog;
use Carbon\Carbon;

class DeactivateInactiveAgents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agents:check-activity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate agents inactive for more than 30 days and process reactivation requests.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for inactive agents...');
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        // Deactivate Physical Agents
        $physicalAgents = Agent::where('status', 'active')
            ->where(function($q) use ($thirtyDaysAgo) {
                $q->where('last_activity_at', '<', $thirtyDaysAgo)
                  ->orWhereNull('last_activity_at');
            })
            ->get();

        foreach ($physicalAgents as $agent) {
            $agent->update([
                'is_deactivated' => true,
                'deactivated_at' => now(),
            ]);

            AgentActivityLog::create([
                'agent_id' => $agent->id,
                'agent_type' => 'agents',
                'activity_type' => 'deactivation',
                'description' => 'Agent automatically deactivated due to 30+ days of inactivity.',
            ]);
            
            $this->info("Deactivated Physical Agent: {$agent->agent_code}");
        }

        // Deactivate Online Agents
        $onlineAgents = AgentApplication::where('status', 'approved')
            ->where('is_deactivated', false)
            ->where(function($q) use ($thirtyDaysAgo) {
                $q->where('last_activity_at', '<', $thirtyDaysAgo)
                  ->orWhereNull('last_activity_at');
            })
            ->get();

        foreach ($onlineAgents as $agent) {
            $agent->update([
                'is_deactivated' => true,
                'deactivated_at' => now(),
            ]);

            AgentActivityLog::create([
                'agent_id' => $agent->id,
                'agent_type' => 'agent_applications',
                'activity_type' => 'deactivation',
                'description' => 'Agent automatically deactivated due to 30+ days of inactivity.',
            ]);

            $this->info("Deactivated Online Agent: {$agent->agent_code}");
        }

        // Process Reactivation Requests (after 24 hours)
        $this->info('Processing reactivation requests...');
        $twentyFourHoursAgo = Carbon::now()->subHours(24);

        $this->reactivateAgents(Agent::class, 'agents', $twentyFourHoursAgo);
        $this->reactivateAgents(AgentApplication::class, 'agent_applications', $twentyFourHoursAgo);

        $this->info('Activity check completed.');
    }

    private function reactivateAgents($modelClass, $agentType, $twentyFourHoursAgo)
    {
        $agents = $modelClass::where('is_deactivated', true)
            ->whereNotNull('metadata->reactivation_requested_at')
            ->get();

        foreach ($agents as $agent) {
            $requestedAt = Carbon::parse($agent->metadata['reactivation_requested_at'] ?? null);
            
            if ($requestedAt && $requestedAt->lt($twentyFourHoursAgo)) {
                $metadata = $agent->metadata ?? [];
                unset($metadata['reactivation_requested_at']);
                
                $agent->update([
                    'is_deactivated' => false,
                    'deactivated_at' => null,
                    'last_activity_at' => now(),
                    'metadata' => $metadata,
                ]);

                AgentActivityLog::create([
                    'agent_id' => $agent->id,
                    'agent_type' => $agentType,
                    'activity_type' => 'reactivation',
                    'description' => 'Agent account reactivated after 24-hour request period.',
                ]);

                $this->info("Reactivated Agent: {$agent->agent_code}");
            }
        }
    }
}
