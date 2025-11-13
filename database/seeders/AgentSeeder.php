<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Team;
use App\Models\Commission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create sample agents
            $agents = [
                [
                    'first_name' => 'John',
                    'last_name' => 'Mukamuri',
                    'email' => 'john.mukamuri@bancozim.com',
                    'phone' => '+263771234567',
                    'national_id' => '63-123456-A-47',
                    'status' => 'active',
                    'type' => 'individual',
                    'region' => 'Harare',
                    'city' => 'Harare',
                    'address' => '123 Samora Machel Avenue, Harare',
                    'commission_rate' => 2.5,
                    'hire_date' => now()->subMonths(6),
                    'bank_name' => 'CBZ Bank',
                    'bank_account' => '12345678901',
                ],
                [
                    'first_name' => 'Mary',
                    'last_name' => 'Chikwanha',
                    'email' => 'mary.chikwanha@bancozim.com',
                    'phone' => '+263772345678',
                    'national_id' => '63-234567-B-58',
                    'status' => 'active',
                    'type' => 'individual',
                    'region' => 'Bulawayo',
                    'city' => 'Bulawayo',
                    'address' => '456 Fife Street, Bulawayo',
                    'commission_rate' => 3.0,
                    'hire_date' => now()->subMonths(4),
                    'bank_name' => 'Steward Bank',
                    'bank_account' => '23456789012',
                ],
                [
                    'first_name' => 'Peter',
                    'last_name' => 'Moyo',
                    'email' => 'peter.moyo@bancozim.com',
                    'phone' => '+263773456789',
                    'national_id' => '63-345678-C-69',
                    'status' => 'active',
                    'type' => 'individual',
                    'region' => 'Mutare',
                    'city' => 'Mutare',
                    'address' => '789 Herbert Chitepo Street, Mutare',
                    'commission_rate' => 2.0,
                    'hire_date' => now()->subMonths(8),
                    'bank_name' => 'CABS',
                    'bank_account' => '34567890123',
                ],
                [
                    'first_name' => 'Grace',
                    'last_name' => 'Sibanda',
                    'email' => 'grace.sibanda@bancozim.com',
                    'phone' => '+263774567890',
                    'national_id' => '63-456789-D-70',
                    'status' => 'active',
                    'type' => 'individual',
                    'region' => 'Gweru',
                    'city' => 'Gweru',
                    'address' => '321 Robert Mugabe Way, Gweru',
                    'commission_rate' => 2.8,
                    'hire_date' => now()->subMonths(3),
                    'bank_name' => 'FBC Bank',
                    'bank_account' => '45678901234',
                ],
                [
                    'first_name' => 'David',
                    'last_name' => 'Ncube',
                    'email' => 'david.ncube@bancozim.com',
                    'phone' => '+263775678901',
                    'national_id' => '63-567890-E-81',
                    'status' => 'active',
                    'type' => 'individual',
                    'region' => 'Harare',
                    'city' => 'Chitungwiza',
                    'address' => '654 Seke Road, Chitungwiza',
                    'commission_rate' => 2.2,
                    'hire_date' => now()->subMonths(5),
                    'bank_name' => 'ZB Bank',
                    'bank_account' => '56789012345',
                ],
                [
                    'first_name' => 'Sarah',
                    'last_name' => 'Madziva',
                    'email' => 'sarah.madziva@bancozim.com',
                    'phone' => '+263776789012',
                    'national_id' => '63-678901-F-92',
                    'status' => 'inactive',
                    'type' => 'individual',
                    'region' => 'Masvingo',
                    'city' => 'Masvingo',
                    'address' => '987 Leopold Takawira Street, Masvingo',
                    'commission_rate' => 1.8,
                    'hire_date' => now()->subMonths(12),
                    'bank_name' => 'Agribank',
                    'bank_account' => '67890123456',
                ],
                [
                    'first_name' => 'Corporate',
                    'last_name' => 'Solutions Ltd',
                    'email' => 'corporate@solutions.co.zw',
                    'phone' => '+263777890123',
                    'national_id' => null,
                    'status' => 'active',
                    'type' => 'corporate',
                    'region' => 'Harare',
                    'city' => 'Harare',
                    'address' => '100 Nelson Mandela Avenue, Harare',
                    'commission_rate' => 4.0,
                    'hire_date' => now()->subMonths(2),
                    'bank_name' => 'Standard Chartered',
                    'bank_account' => '78901234567',
                ],
            ];

            foreach ($agents as $agentData) {
                $agent = Agent::create($agentData);
                
                // Generate referral links for each agent
                $agent->generateReferralLink('Default Campaign');
                if (rand(0, 1)) {
                    $agent->generateReferralLink('Summer 2024 Campaign');
                }
            }

            // Create teams
            $teams = [
                [
                    'name' => 'Harare Team',
                    'description' => 'Sales team covering Harare and surrounding areas',
                    'status' => 'active',
                    'team_commission_rate' => 0.5,
                ],
                [
                    'name' => 'Bulawayo Team',
                    'description' => 'Sales team covering Bulawayo and Matabeleland',
                    'status' => 'active',
                    'team_commission_rate' => 0.5,
                ],
                [
                    'name' => 'Regional Team',
                    'description' => 'Sales team covering other regions',
                    'status' => 'active',
                    'team_commission_rate' => 0.3,
                ],
            ];

            foreach ($teams as $teamData) {
                Team::create($teamData);
            }

            // Assign agents to teams
            $harareTeam = Team::where('name', 'Harare Team')->first();
            $bulawayoTeam = Team::where('name', 'Bulawayo Team')->first();
            $regionalTeam = Team::where('name', 'Regional Team')->first();

            // Harare team members
            $harareAgents = Agent::whereIn('region', ['Harare'])->get();
            foreach ($harareAgents as $agent) {
                $harareTeam->agents()->attach($agent->id, [
                    'joined_at' => $agent->hire_date,
                    'role' => $agent->email === 'john.mukamuri@bancozim.com' ? 'leader' : 'member',
                    'is_active' => true,
                ]);
            }

            // Set team leader
            $harareTeam->update(['team_leader_id' => Agent::where('email', 'john.mukamuri@bancozim.com')->first()->id]);

            // Bulawayo team members
            $bulawayoAgents = Agent::whereIn('region', ['Bulawayo'])->get();
            foreach ($bulawayoAgents as $agent) {
                $bulawayoTeam->agents()->attach($agent->id, [
                    'joined_at' => $agent->hire_date,
                    'role' => 'leader',
                    'is_active' => true,
                ]);
            }
            $bulawayoTeam->update(['team_leader_id' => Agent::where('email', 'mary.chikwanha@bancozim.com')->first()->id]);

            // Regional team members
            $regionalAgents = Agent::whereNotIn('region', ['Harare', 'Bulawayo'])->get();
            foreach ($regionalAgents as $agent) {
                $regionalTeam->agents()->attach($agent->id, [
                    'joined_at' => $agent->hire_date,
                    'role' => 'member',
                    'is_active' => $agent->status === 'active',
                ]);
            }
            $regionalTeam->update(['team_leader_id' => Agent::where('email', 'peter.moyo@bancozim.com')->first()->id]);

            // Create sample commissions
            $activeAgents = Agent::active()->get();
            foreach ($activeAgents as $agent) {
                // Create 2-5 sample commissions per agent
                $commissionCount = rand(2, 5);
                for ($i = 0; $i < $commissionCount; $i++) {
                    $baseAmount = rand(500, 2000);
                    $commissionAmount = ($baseAmount * $agent->commission_rate) / 100;
                    
                    Commission::create([
                        'agent_id' => $agent->id,
                        'type' => 'application',
                        'amount' => $commissionAmount,
                        'rate' => $agent->commission_rate,
                        'base_amount' => $baseAmount,
                        'status' => ['pending', 'approved', 'paid'][rand(0, 2)],
                        'earned_date' => now()->subDays(rand(1, 30)),
                        'paid_date' => rand(0, 1) ? now()->subDays(rand(1, 15)) : null,
                        'payment_method' => rand(0, 1) ? 'Bank Transfer' : null,
                        'metadata' => [
                            'loan_amount' => $baseAmount,
                            'calculated_at' => now()->toISOString(),
                        ],
                    ]);
                }
            }

            $this->command->info('Created ' . Agent::count() . ' agents');
            $this->command->info('Created ' . Team::count() . ' teams');
            $this->command->info('Created ' . Commission::count() . ' commissions');
        });
    }
}
