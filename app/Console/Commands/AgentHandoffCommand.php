<?php

namespace App\Console\Commands;

use App\Services\AIAgents\HandoffManager;
use Illuminate\Console\Command;

class AgentHandoffCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:handoff 
                            {action : The action to perform (initiate|status|workload|report)}
                            {--from= : Source agent name}
                            {--to= : Target agent name}
                            {--task= : Task ID}
                            {--data= : JSON encoded handoff data}
                            {--agent= : Agent name for workload check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage AI agent handoffs for Bancozim development';

    protected HandoffManager $handoffManager;

    /**
     * Create a new command instance.
     */
    public function __construct(HandoffManager $handoffManager)
    {
        parent::__construct();
        $this->handoffManager = $handoffManager;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'initiate':
                return $this->initiateHandoff();
            case 'status':
                return $this->checkHandoffStatus();
            case 'workload':
                return $this->checkAgentWorkload();
            case 'report':
                return $this->generateReport();
            case 'example':
                return $this->runExample();
            default:
                $this->error("Invalid action: {$action}");

                return Command::FAILURE;
        }
    }

    /**
     * Initiate a new handoff
     */
    protected function initiateHandoff(): int
    {
        $fromAgent = $this->option('from');
        $toAgent = $this->option('to');
        $taskId = $this->option('task');
        $dataJson = $this->option('data');

        if (! $fromAgent || ! $toAgent || ! $taskId) {
            $this->error('Missing required options: --from, --to, --task');

            return Command::FAILURE;
        }

        $handoffData = $dataJson ? json_decode($dataJson, true) : $this->getDefaultHandoffData();

        if (json_last_error() !== JSON_ERROR_NONE && $dataJson) {
            $this->error('Invalid JSON in --data option');

            return Command::FAILURE;
        }

        $this->info("Initiating handoff: {$fromAgent} → {$toAgent}");
        $this->newLine();

        $success = $this->handoffManager->initiateHandoff(
            $fromAgent,
            $toAgent,
            $taskId,
            $handoffData
        );

        if ($success) {
            $this->info('✅ Handoff completed successfully!');
            $this->table(
                ['Property', 'Value'],
                [
                    ['From Agent', $fromAgent],
                    ['To Agent', $toAgent],
                    ['Task ID', $taskId],
                    ['Task Name', $handoffData['task_name'] ?? 'N/A'],
                    ['Completion', ($handoffData['completion_percentage'] ?? 0).'%'],
                    ['Quality Score', ($handoffData['quality_score'] ?? 0).'/100'],
                ]
            );

            return Command::SUCCESS;
        } else {
            $this->error('❌ Handoff failed. Check logs for details.');

            return Command::FAILURE;
        }
    }

    /**
     * Check handoff status
     */
    protected function checkHandoffStatus(): int
    {
        $taskId = $this->option('task');

        if (! $taskId) {
            $this->error('Missing required option: --task');

            return Command::FAILURE;
        }

        $status = $this->handoffManager->getHandoffStatus($taskId);

        if ($status) {
            $this->info("Handoff Status for Task: {$taskId}");
            $this->table(
                ['Property', 'Value'],
                [
                    ['Task ID', $status['task_id']],
                    ['From Agent', $status['from_agent']],
                    ['To Agent', $status['to_agent']],
                    ['Handoff Time', $status['handoff_time']],
                    ['Completion Status', $status['completion_status']],
                    ['Quality Score', $status['quality_score'].'/100'],
                    ['Next Steps', $status['next_steps_count'].' items'],
                ]
            );

            return Command::SUCCESS;
        } else {
            $this->warn("No active handoff found for task: {$taskId}");

            return Command::FAILURE;
        }
    }

    /**
     * Check agent workload
     */
    protected function checkAgentWorkload(): int
    {
        $agentName = $this->option('agent');

        if (! $agentName) {
            $this->error('Missing required option: --agent');

            return Command::FAILURE;
        }

        $workload = $this->handoffManager->getAgentWorkload($agentName);

        $this->info("Workload for Agent: {$agentName}");
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Incoming Tasks', $workload['incoming_tasks']],
                ['Completed Handoffs', $workload['completed_handoffs']],
            ]
        );

        if (! empty($workload['pending_tasks'])) {
            $this->newLine();
            $this->info('Pending Tasks:');
            $this->table(
                ['Task ID', 'Task Name', 'From Agent', 'Handoff Time'],
                array_map(function ($task) {
                    return [
                        $task['task_id'],
                        $task['task_name'],
                        $task['from_agent'],
                        $task['handoff_time'],
                    ];
                }, $workload['pending_tasks'])
            );
        }

        return Command::SUCCESS;
    }

    /**
     * Generate workflow report
     */
    protected function generateReport(): int
    {
        $this->info('Generating Workflow Report...');
        $this->newLine();

        $report = $this->handoffManager->generateWorkflowReport();

        $this->info('📊 Workflow Report Generated');
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Report Time', $report['report_timestamp']],
                ['Total Handoffs', $report['total_handoffs']],
                ['Active Handoffs', $report['active_handoffs']],
            ]
        );

        if (! empty($report['agent_performance'])) {
            $this->newLine();
            $this->info('Agent Performance:');
            $performanceData = [];
            foreach ($report['agent_performance'] as $agent => $metrics) {
                $performanceData[] = [
                    $agent,
                    $metrics['total_handoffs'],
                    $metrics['average_quality'].'/100',
                    $metrics['average_time_hours'].' hrs',
                    $metrics['efficiency_score'],
                ];
            }
            $this->table(
                ['Agent', 'Handoffs', 'Avg Quality', 'Avg Time', 'Efficiency'],
                $performanceData
            );
        }

        if (! empty($report['bottlenecks'])) {
            $this->newLine();
            $this->warn('⚠️ Bottlenecks Detected:');
            foreach ($report['bottlenecks'] as $bottleneck) {
                $this->line("  - {$bottleneck}");
            }
        }

        if (! empty($report['recommendations'])) {
            $this->newLine();
            $this->info('💡 Recommendations:');
            foreach ($report['recommendations'] as $recommendation) {
                $this->line("  - {$recommendation}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Run example handoff for demonstration
     */
    protected function runExample(): int
    {
        $this->info('Running Example Handoff for Bancozim Development');
        $this->newLine();

        // Example: Database to Laravel handoff
        $handoffData = [
            'task_name' => 'MySQL Schema Implementation for Loan Applications',
            'completion_status' => 'completed',
            'completion_percentage' => 95,
            'quality_score' => 92,
            'time_spent_hours' => 8.5,
            'deliverables' => [
                'primary' => 'Complete MySQL schema for loan applications and products',
                'migrations' => 'Laravel migration files for all tables',
                'seeders' => 'Database seeders with test data',
                'indexes' => 'Optimized indexes for query performance',
                'documentation' => 'Schema documentation with relationships',
            ],
            'quality_metrics' => [
                'table_count' => '24 tables',
                'index_optimization' => '60-80% query improvement',
                'foreign_keys' => 'All relationships enforced',
                'data_integrity' => 'Constraints implemented',
            ],
            'integration_points' => [
                'loan_applications' => 'Schema ready for Laravel models',
                'products_catalog' => 'Product tables configured',
                'agent_management' => 'Agent and commission tables ready',
                'multi_channel' => 'Channel state management tables created',
            ],
            'next_steps' => [
                'Create Eloquent models for all tables',
                'Implement repository pattern for data access',
                'Add Laravel validation rules',
                'Create API resources for JSON responses',
                'Implement service layer for business logic',
            ],
            'files_modified' => [
                'database/migrations/2025_01_01_create_loan_applications_table.php',
                'database/migrations/2025_01_01_create_products_table.php',
                'database/migrations/2025_01_01_create_agents_table.php',
                'database/seeders/DatabaseSeeder.php',
            ],
            'recommendations' => [
                'Consider implementing Laravel Scout for product search',
                'Add Redis caching for frequently accessed data',
                'Implement database backup strategy',
                'Consider read/write database splitting for scale',
            ],
        ];

        $success = $this->handoffManager->initiateHandoff(
            'claude-db',
            'claude-laravel',
            'BANC_'.time(),
            $handoffData
        );

        if ($success) {
            $this->info('✅ Example handoff completed successfully!');
            $this->newLine();
            $this->info('Check the generated documents in:');
            $this->line('  - storage/app/ai-agents/reports/handoffs/');
            $this->line('  - storage/app/ai-agents/configs/');
        } else {
            $this->error('❌ Example handoff failed.');
        }

        return $success ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Get default handoff data structure
     */
    protected function getDefaultHandoffData(): array
    {
        return [
            'task_name' => 'Development Task',
            'completion_status' => 'in_progress',
            'completion_percentage' => 80,
            'quality_score' => 85,
            'time_spent_hours' => 4.0,
            'deliverables' => [
                'primary' => 'Main deliverable description',
            ],
            'quality_metrics' => [
                'test_coverage' => '85%',
            ],
            'integration_points' => [],
            'next_steps' => [
                'Continue implementation',
                'Add tests',
                'Update documentation',
            ],
            'template_deviations' => [],
            'blockers_resolved' => [],
            'recommendations' => [],
            'files_modified' => [],
        ];
    }
}
