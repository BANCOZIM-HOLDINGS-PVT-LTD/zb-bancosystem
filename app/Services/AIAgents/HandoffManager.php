<?php

namespace App\Services\AIAgents;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Bancozim Platform Agent Handoff Manager
 * Manages seamless handoffs between specialized AI agents
 */
class HandoffManager
{
    protected Collection $handoffHistory;
    protected array $activeHandoffs = [];
    protected string $reportsPath;
    protected string $templatesPath;
    protected string $configsPath;
    
    /**
     * Agent workflow definitions with valid handoff paths
     */
    protected array $agentWorkflows = [
        'claude-business' => ['claude-db', 'claude-ux'],
        'claude-db' => ['claude-laravel'],
        'claude-laravel' => ['claude-react', 'claude-filament', 'claude-test'],
        'claude-react' => ['claude-test', 'claude-ux'],
        'claude-filament' => ['claude-test', 'claude-integration'],
        'claude-integration' => ['claude-test', 'claude-security'],
        'claude-test' => ['claude-security', 'claude-performance', 'claude-devops'],
        'claude-security' => ['claude-compliance', 'claude-devops'],
        'claude-performance' => ['claude-devops'],
        'claude-ux' => ['claude-react', 'claude-docs'],
        'claude-docs' => ['claude-compliance'],
        'claude-compliance' => ['claude-devops'],
        'claude-devops' => [] // Final stage
    ];
    
    public function __construct()
    {
        $this->handoffHistory = collect();
        $this->reportsPath = storage_path('app/ai-agents/reports/handoffs');
        $this->templatesPath = base_path('scripts/ai-agents/templates');
        $this->configsPath = storage_path('app/ai-agents/configs');
        
        // Ensure directories exist
        $this->ensureDirectoriesExist();
    }
    
    /**
     * Ensure required directories exist
     */
    protected function ensureDirectoriesExist(): void
    {
        if (!is_dir($this->reportsPath)) {
            mkdir($this->reportsPath, 0755, true);
        }
        if (!is_dir($this->configsPath)) {
            mkdir($this->configsPath, 0755, true);
        }
    }
    
    /**
     * Initiate a handoff between agents with validation
     */
    public function initiateHandoff(
        string $fromAgent,
        string $toAgent,
        string $taskId,
        array $handoffData
    ): bool {
        Log::info("Initiating handoff: {$fromAgent} -> {$toAgent} for task {$taskId}");
        
        // Validate handoff path
        if (!$this->validateHandoffPath($fromAgent, $toAgent)) {
            Log::error("Invalid handoff path: {$fromAgent} -> {$toAgent}");
            return false;
        }
        
        // Validate completion status
        if (!$this->validateCompletionStatus($handoffData)) {
            Log::error("Handoff validation failed for task {$taskId}");
            return false;
        }
        
        // Create handoff record
        $handoff = new HandoffData(
            fromAgent: $fromAgent,
            toAgent: $toAgent,
            taskId: $taskId,
            taskName: $handoffData['task_name'] ?? '',
            completionStatus: $handoffData['completion_status'] ?? 'incomplete',
            completionPercentage: $handoffData['completion_percentage'] ?? 0,
            qualityScore: $handoffData['quality_score'] ?? 0,
            timeSpentHours: $handoffData['time_spent_hours'] ?? 0,
            deliverables: $handoffData['deliverables'] ?? [],
            qualityMetrics: $handoffData['quality_metrics'] ?? [],
            integrationPoints: $handoffData['integration_points'] ?? [],
            nextSteps: $handoffData['next_steps'] ?? [],
            templateDeviations: $handoffData['template_deviations'] ?? [],
            blockersResolved: $handoffData['blockers_resolved'] ?? [],
            recommendations: $handoffData['recommendations'] ?? [],
            filesModified: $handoffData['files_modified'] ?? [],
            handoffTimestamp: Carbon::now()
        );
        
        // Generate handoff document
        $handoffDoc = $this->generateHandoffDocument($handoff);
        
        // Save handoff document
        $this->saveHandoffDocument($handoff, $handoffDoc);
        
        // Update tracking
        $this->handoffHistory->push($handoff);
        $this->activeHandoffs[$taskId] = $handoff;
        
        // Generate instructions for receiving agent
        $instructions = $this->generateReceivingAgentInstructions($handoff);
        $this->saveAgentInstructions($toAgent, $taskId, $instructions);
        
        Log::info("Handoff completed successfully: {$fromAgent} -> {$toAgent}");
        return true;
    }
    
    /**
     * Validate that the handoff path is allowed
     */
    protected function validateHandoffPath(string $fromAgent, string $toAgent): bool
    {
        // Check if from_agent exists in workflows
        if (!isset($this->agentWorkflows[$fromAgent])) {
            return false;
        }
        
        // Check if to_agent is a valid next step
        $validNext = $this->agentWorkflows[$fromAgent];
        return in_array($toAgent, $validNext) || empty($validNext);
    }
    
    /**
     * Validate that the work is ready for handoff
     */
    protected function validateCompletionStatus(array $handoffData): bool
    {
        $requiredFields = ['task_name', 'completion_status', 'deliverables'];
        
        foreach ($requiredFields as $field) {
            if (!isset($handoffData[$field])) {
                Log::error("Missing required field: {$field}");
                return false;
            }
        }
        
        // Check completion percentage
        $completion = $handoffData['completion_percentage'] ?? 0;
        if ($completion < 80) {
            Log::warning("Low completion percentage: {$completion}%");
            return false;
        }
        
        // Check quality score
        $quality = $handoffData['quality_score'] ?? 0;
        if ($quality < 70) {
            Log::warning("Low quality score: {$quality}");
            return false;
        }
        
        return true;
    }
    
    /**
     * Generate formal handoff document
     */
    protected function generateHandoffDocument(HandoffData $handoff): string
    {
        $template = "# AGENT HANDOFF: {$handoff->fromAgent} → {$handoff->toAgent}\n";
        $template .= "## Task: {$handoff->taskName}\n";
        $template .= "### Handoff ID: {$handoff->taskId}\n";
        $template .= "### Timestamp: {$handoff->handoffTimestamp->toIso8601String()}\n\n";
        $template .= "---\n\n";
        
        $template .= "## COMPLETION SUMMARY\n";
        $template .= "- **Task Status**: {$handoff->completionStatus}\n";
        $template .= "- **Completion**: {$handoff->completionPercentage}%\n";
        $template .= "- **Quality Score**: {$handoff->qualityScore}/100\n";
        $template .= "- **Time Spent**: {$handoff->timeSpentHours} hours\n\n";
        
        $template .= "## DELIVERABLES\n";
        foreach ($handoff->deliverables as $type => $description) {
            $template .= "- **{$type}**: {$description}\n";
        }
        
        $template .= "\n## QUALITY METRICS\n";
        foreach ($handoff->qualityMetrics as $metric => $value) {
            $template .= "- **{$metric}**: {$value}\n";
        }
        
        $template .= "\n## INTEGRATION POINTS\n";
        foreach ($handoff->integrationPoints as $integration => $status) {
            $template .= "- **{$integration}**: {$status}\n";
        }
        
        $template .= "\n## NEXT STEPS FOR {$handoff->toAgent}\n";
        foreach ($handoff->nextSteps as $i => $step) {
            $num = $i + 1;
            $template .= "{$num}. {$step}\n";
        }
        
        if (!empty($handoff->templateDeviations)) {
            $template .= "\n## DEVIATIONS FROM TEMPLATE\n";
            foreach ($handoff->templateDeviations as $deviation) {
                $template .= "- {$deviation}\n";
            }
        }
        
        if (!empty($handoff->blockersResolved)) {
            $template .= "\n## BLOCKERS RESOLVED\n";
            foreach ($handoff->blockersResolved as $blocker) {
                $template .= "- {$blocker}\n";
            }
        }
        
        if (!empty($handoff->recommendations)) {
            $template .= "\n## RECOMMENDATIONS\n";
            foreach ($handoff->recommendations as $rec) {
                $template .= "- {$rec}\n";
            }
        }
        
        if (!empty($handoff->filesModified)) {
            $template .= "\n## FILES MODIFIED\n";
            foreach ($handoff->filesModified as $filePath) {
                $template .= "- {$filePath}\n";
            }
        }
        
        $template .= "\n---\n\n";
        $template .= "**Handoff Status**: COMPLETE\n";
        $template .= "**Ready for {$handoff->toAgent}**: YES\n\n";
        $template .= "*This handoff document was generated automatically by the Bancozim AI Agent Coordination System.*\n";
        
        return $template;
    }
    
    /**
     * Save handoff document to file
     */
    protected function saveHandoffDocument(HandoffData $handoff, string $document): void
    {
        $filename = sprintf(
            "handoff_%s_%s_to_%s_%s.md",
            $handoff->taskId,
            $handoff->fromAgent,
            $handoff->toAgent,
            $handoff->handoffTimestamp->format('Ymd_His')
        );
        
        $filePath = $this->reportsPath . '/' . $filename;
        file_put_contents($filePath, $document);
        
        Log::info("Handoff document saved: {$filePath}");
    }
    
    /**
     * Generate specific instructions for the receiving agent
     */
    protected function generateReceivingAgentInstructions(HandoffData $handoff): string
    {
        // Load agent template
        $agentTemplate = $this->loadAgentTemplate($handoff->toAgent);
        
        $instructions = "# TASK CONTINUATION: {$handoff->taskName}\n";
        $instructions .= "## Agent: {$handoff->toAgent}\n";
        $instructions .= "### Received from: {$handoff->fromAgent}\n\n";
        $instructions .= "---\n\n";
        
        $instructions .= "## CONTEXT FROM PREVIOUS WORK\n";
        $instructions .= "- **Previous Agent**: {$handoff->fromAgent}\n";
        $instructions .= "- **Work Completed**: {$handoff->completionPercentage}%\n";
        $instructions .= "- **Quality Score**: {$handoff->qualityScore}/100\n\n";
        
        $instructions .= "## YOUR STARTING POINT\n";
        foreach ($handoff->deliverables as $type => $description) {
            $instructions .= "- **{$type}**: {$description}\n";
        }
        
        $instructions .= "\n## IMMEDIATE NEXT STEPS\n";
        $topSteps = array_slice($handoff->nextSteps, 0, 3);
        foreach ($topSteps as $i => $step) {
            $num = $i + 1;
            $instructions .= "{$num}. {$step}\n";
        }
        
        $instructions .= "\n## INTEGRATION REQUIREMENTS\n";
        $instructions .= "Based on previous work, ensure compatibility with:\n";
        foreach ($handoff->integrationPoints as $integration => $status) {
            $instructions .= "- **{$integration}**: {$status}\n";
        }
        
        if (!empty($handoff->recommendations)) {
            $instructions .= "\n## RECOMMENDATIONS FROM {$handoff->fromAgent}\n";
            foreach ($handoff->recommendations as $rec) {
                $instructions .= "- {$rec}\n";
            }
        }
        
        // Add agent-specific template guidance
        if ($agentTemplate) {
            $truncated = substr($agentTemplate, 0, 1000);
            $templateFile = str_replace('-', '_', strtolower($handoff->toAgent));
            $instructions .= "\n## AGENT-SPECIFIC GUIDANCE\n";
            $instructions .= "{$truncated}...\n\n";
            $instructions .= "*Full template available in: scripts/ai-agents/templates/{$templateFile}.md*\n";
        }
        
        $instructions .= "\n---\n\n";
        $instructions .= "## QUALITY STANDARDS TO MAINTAIN\n";
        $instructions .= "- Continue with the established quality score of {$handoff->qualityScore}/100\n";
        $instructions .= "- Follow Bancozim platform patterns and conventions\n";
        $instructions .= "- Ensure backward compatibility with {$handoff->fromAgent}'s work\n";
        $instructions .= "- Document any changes or enhancements made\n\n";
        
        $instructions .= "**Task ID**: {$handoff->taskId}\n";
        $instructions .= "**Expected Completion**: Continue from {$handoff->completionPercentage}% to 100%\n\n";
        $instructions .= "Begin work immediately. Update Master Coordinator upon completion.\n";
        
        return $instructions;
    }
    
    /**
     * Load template for specific agent
     */
    protected function loadAgentTemplate(string $agentName): ?string
    {
        $templateFile = $this->templatesPath . '/' . str_replace('-', '_', strtolower($agentName)) . '.md';
        
        if (file_exists($templateFile)) {
            try {
                return file_get_contents($templateFile);
            } catch (\Exception $e) {
                Log::error("Error loading template for {$agentName}: " . $e->getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Save instructions for receiving agent
     */
    protected function saveAgentInstructions(string $agentName, string $taskId, string $instructions): void
    {
        $filename = sprintf(
            "instructions_%s_%s.md",
            str_replace('-', '_', strtolower($agentName)),
            $taskId
        );
        
        $filePath = $this->configsPath . '/' . $filename;
        file_put_contents($filePath, $instructions);
        
        Log::info("Agent instructions saved: {$filePath}");
    }
    
    /**
     * Get status of a specific handoff
     */
    public function getHandoffStatus(string $taskId): ?array
    {
        if (!isset($this->activeHandoffs[$taskId])) {
            return null;
        }
        
        $handoff = $this->activeHandoffs[$taskId];
        return [
            'task_id' => $taskId,
            'from_agent' => $handoff->fromAgent,
            'to_agent' => $handoff->toAgent,
            'handoff_time' => $handoff->handoffTimestamp->toIso8601String(),
            'completion_status' => $handoff->completionStatus,
            'quality_score' => $handoff->qualityScore,
            'next_steps_count' => count($handoff->nextSteps)
        ];
    }
    
    /**
     * Get current workload for an agent
     */
    public function getAgentWorkload(string $agentName): array
    {
        // Count incoming tasks
        $incomingTasks = collect($this->activeHandoffs)
            ->filter(fn($h) => $h->toAgent === $agentName)
            ->values();
        
        // Count completed handoffs
        $completedHandoffs = $this->handoffHistory
            ->filter(fn($h) => $h->fromAgent === $agentName);
        
        return [
            'agent_name' => $agentName,
            'incoming_tasks' => $incomingTasks->count(),
            'completed_handoffs' => $completedHandoffs->count(),
            'pending_tasks' => $incomingTasks->map(fn($h) => [
                'task_id' => $h->taskId,
                'task_name' => $h->taskName,
                'from_agent' => $h->fromAgent,
                'handoff_time' => $h->handoffTimestamp->toIso8601String()
            ])->toArray()
        ];
    }
    
    /**
     * Generate comprehensive workflow report
     */
    public function generateWorkflowReport(): array
    {
        $report = [
            'report_timestamp' => Carbon::now()->toIso8601String(),
            'total_handoffs' => $this->handoffHistory->count(),
            'active_handoffs' => count($this->activeHandoffs),
            'agent_performance' => [],
            'workflow_efficiency' => [],
            'bottlenecks' => [],
            'recommendations' => []
        ];
        
        // Calculate agent performance
        foreach (array_keys($this->agentWorkflows) as $agent) {
            $agentHandoffs = $this->handoffHistory
                ->filter(fn($h) => $h->fromAgent === $agent);
            
            if ($agentHandoffs->isNotEmpty()) {
                $avgQuality = $agentHandoffs->avg('qualityScore');
                $avgTime = $agentHandoffs->avg('timeSpentHours');
                
                $report['agent_performance'][$agent] = [
                    'total_handoffs' => $agentHandoffs->count(),
                    'average_quality' => round($avgQuality, 2),
                    'average_time_hours' => round($avgTime, 2),
                    'efficiency_score' => $avgTime > 0 ? round(($avgQuality / $avgTime) * 10, 2) : 0
                ];
            }
        }
        
        // Identify bottlenecks
        $agentWorkloads = [];
        foreach (array_keys($this->agentWorkflows) as $agent) {
            $workload = $this->getAgentWorkload($agent);
            $agentWorkloads[$agent] = $workload['incoming_tasks'];
        }
        
        // Find agents with high workload
        $avgWorkload = count($agentWorkloads) > 0 
            ? array_sum($agentWorkloads) / count($agentWorkloads) 
            : 0;
            
        $bottleneckAgents = array_filter(
            $agentWorkloads,
            fn($workload) => $workload > $avgWorkload * 1.5
        );
        
        $report['bottlenecks'] = array_keys($bottleneckAgents);
        
        // Generate recommendations
        if (!empty($bottleneckAgents)) {
            $report['recommendations'][] = sprintf(
                "Consider load balancing for agents: %s",
                implode(', ', array_keys($bottleneckAgents))
            );
        }
        
        // Save report
        $reportPath = $this->reportsPath . '/workflow_report_' . Carbon::now()->format('Ymd_His') . '.json';
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        Log::info("Workflow report saved: {$reportPath}");
        return $report;
    }
}

/**
 * Data transfer object for handoff data
 */
class HandoffData
{
    public function __construct(
        public string $fromAgent,
        public string $toAgent,
        public string $taskId,
        public string $taskName,
        public string $completionStatus,
        public int $completionPercentage,
        public int $qualityScore,
        public float $timeSpentHours,
        public array $deliverables,
        public array $qualityMetrics,
        public array $integrationPoints,
        public array $nextSteps,
        public array $templateDeviations,
        public array $blockersResolved,
        public array $recommendations,
        public array $filesModified,
        public Carbon $handoffTimestamp
    ) {}
}