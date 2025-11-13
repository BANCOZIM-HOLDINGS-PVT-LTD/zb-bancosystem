<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentReferralLink;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AgentReferralLinkService
{
    /**
     * Generate a new referral link for an agent
     */
    public function generateReferralLink(Agent $agent, array $options = []): AgentReferralLink
    {
        $code = $this->generateUniqueCode($agent);
        
        $referralLink = AgentReferralLink::create([
            'agent_id' => $agent->id,
            'code' => $code,
            'campaign_name' => $options['campaign_name'] ?? 'Default Campaign',
            'description' => $options['description'] ?? "Referral link for {$agent->full_name}",
            'expires_at' => $options['expires_at'] ?? now()->addMonths(6),
            'max_uses' => $options['max_uses'] ?? null,
            'is_active' => true,
            'metadata' => $options['metadata'] ?? [],
        ]);

        Log::info('Referral link generated', [
            'agent_id' => $agent->id,
            'agent_name' => $agent->full_name,
            'code' => $code,
            'campaign' => $options['campaign_name'] ?? 'Default Campaign',
        ]);

        return $referralLink;
    }

    /**
     * Generate multiple referral links for an agent (for different campaigns)
     */
    public function generateMultipleLinks(Agent $agent, array $campaigns): array
    {
        $links = [];
        
        foreach ($campaigns as $campaign) {
            $links[] = $this->generateReferralLink($agent, [
                'campaign_name' => $campaign['name'],
                'description' => $campaign['description'] ?? "Campaign: {$campaign['name']}",
                'expires_at' => $campaign['expires_at'] ?? now()->addMonths(6),
                'max_uses' => $campaign['max_uses'] ?? null,
                'metadata' => $campaign['metadata'] ?? [],
            ]);
        }

        return $links;
    }

    /**
     * Deactivate a referral link
     */
    public function deactivateLink(AgentReferralLink $link, string $reason = null): bool
    {
        $link->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => $reason,
        ]);

        Log::info('Referral link deactivated', [
            'link_id' => $link->id,
            'agent_id' => $link->agent_id,
            'code' => $link->code,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Reactivate a referral link
     */
    public function reactivateLink(AgentReferralLink $link): bool
    {
        // Check if link is not expired
        if ($link->expires_at && $link->expires_at->isPast()) {
            return false;
        }

        $link->update([
            'is_active' => true,
            'deactivated_at' => null,
            'deactivation_reason' => null,
        ]);

        Log::info('Referral link reactivated', [
            'link_id' => $link->id,
            'agent_id' => $link->agent_id,
            'code' => $link->code,
        ]);

        return true;
    }

    /**
     * Get referral link performance analytics
     */
    public function getLinkAnalytics(AgentReferralLink $link): array
    {
        $clicks = $link->clicks()->count();
        $uniqueClicks = $link->clicks()->distinct('ip_address')->count();
        $conversions = $link->applications()->count();
        $approvedConversions = $link->applications()->where('current_step', 'approved')->count();
        
        $conversionRate = $clicks > 0 ? ($conversions / $clicks) * 100 : 0;
        $approvalRate = $conversions > 0 ? ($approvedConversions / $conversions) * 100 : 0;

        return [
            'total_clicks' => $clicks,
            'unique_clicks' => $uniqueClicks,
            'total_conversions' => $conversions,
            'approved_conversions' => $approvedConversions,
            'conversion_rate' => round($conversionRate, 2),
            'approval_rate' => round($approvalRate, 2),
            'click_to_approval_rate' => $clicks > 0 ? round(($approvedConversions / $clicks) * 100, 2) : 0,
            'last_click_date' => $link->clicks()->latest()->first()?->created_at,
            'last_conversion_date' => $link->applications()->latest()->first()?->created_at,
        ];
    }

    /**
     * Get agent performance summary across all links
     */
    public function getAgentPerformance(Agent $agent, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonths(3);
        $endDate = $endDate ?? now();

        $links = $agent->referralLinks()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalClicks = 0;
        $totalConversions = 0;
        $totalApprovedConversions = 0;
        $totalCommissionEarned = 0;

        foreach ($links as $link) {
            $analytics = $this->getLinkAnalytics($link);
            $totalClicks += $analytics['total_clicks'];
            $totalConversions += $analytics['total_conversions'];
            $totalApprovedConversions += $analytics['approved_conversions'];
        }

        // Calculate commission earned
        $totalCommissionEarned = $agent->commissions()
            ->whereBetween('earned_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('amount');

        $conversionRate = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;
        $approvalRate = $totalConversions > 0 ? ($totalApprovedConversions / $totalConversions) * 100 : 0;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'total_links' => $links->count(),
            'active_links' => $links->where('is_active', true)->count(),
            'total_clicks' => $totalClicks,
            'total_conversions' => $totalConversions,
            'approved_conversions' => $totalApprovedConversions,
            'conversion_rate' => round($conversionRate, 2),
            'approval_rate' => round($approvalRate, 2),
            'commission_earned' => $totalCommissionEarned,
            'average_commission_per_conversion' => $totalApprovedConversions > 0 ? 
                round($totalCommissionEarned / $totalApprovedConversions, 2) : 0,
            'best_performing_link' => $this->getBestPerformingLink($links),
        ];
    }

    /**
     * Generate bulk referral links for multiple agents
     */
    public function generateBulkLinks(array $agentIds, array $campaignOptions = []): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($agentIds as $agentId) {
            try {
                $agent = Agent::findOrFail($agentId);
                $link = $this->generateReferralLink($agent, $campaignOptions);
                
                $results['success'][] = [
                    'agent_id' => $agentId,
                    'agent_name' => $agent->full_name,
                    'link_id' => $link->id,
                    'code' => $link->code,
                    'url' => $link->url,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'agent_id' => $agentId,
                    'error' => $e->getMessage(),
                ];
                
                Log::error('Bulk referral link generation failed', [
                    'agent_id' => $agentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Clean up expired referral links
     */
    public function cleanupExpiredLinks(): int
    {
        $expiredCount = AgentReferralLink::where('expires_at', '<', now())
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'deactivated_at' => now(),
                'deactivation_reason' => 'Expired',
            ]);

        Log::info('Expired referral links cleaned up', [
            'count' => $expiredCount,
        ]);

        return $expiredCount;
    }

    /**
     * Generate a unique referral code for an agent
     */
    private function generateUniqueCode(Agent $agent): string
    {
        $baseCode = 'ref_' . strtoupper(substr($agent->first_name, 0, 3)) . 
                   strtoupper(substr($agent->last_name, 0, 3)) . 
                   strtoupper(Str::random(2));

        $code = $baseCode . '_' . Str::random(10);

        // Ensure uniqueness
        while (AgentReferralLink::where('code', $code)->exists()) {
            $code = $baseCode . '_' . Str::random(10);
        }

        return $code;
    }

    /**
     * Get the best performing link from a collection
     */
    private function getBestPerformingLink($links): ?array
    {
        $bestLink = null;
        $bestScore = 0;

        foreach ($links as $link) {
            $analytics = $this->getLinkAnalytics($link);
            // Score based on conversions and approval rate
            $score = $analytics['approved_conversions'] * $analytics['approval_rate'];
            
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestLink = [
                    'link_id' => $link->id,
                    'code' => $link->code,
                    'campaign' => $link->campaign_name,
                    'score' => $score,
                    'analytics' => $analytics,
                ];
            }
        }

        return $bestLink;
    }
}
