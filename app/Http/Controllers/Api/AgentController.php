<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * Get all active agents
     */
    public function index(Request $request): JsonResponse
    {
        $query = Agent::active();

        // Filter by type if provided
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filter by region if provided
        if ($request->has('region')) {
            $query->inRegion($request->region);
        }

        // Search functionality
        if ($request->has('search')) {
            $query->search($request->search);
        }

        $agents = $query->select([
            'id',
            'agent_code',
            'first_name',
            'last_name',
            'email',
            'phone',
            'type',
            'region',
            'commission_rate',
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $agents->map(function ($agent) {
                return [
                    'id' => $agent->id,
                    'agent_code' => $agent->agent_code,
                    'name' => $agent->full_name,
                    'display_name' => $agent->display_name,
                    'email' => $agent->email,
                    'phone' => $agent->phone,
                    'type' => $agent->type,
                    'region' => $agent->region,
                    'commission_rate' => $agent->commission_rate,
                ];
            }),
        ]);
    }

    /**
     * Get agent by code
     */
    public function getByCode(string $code): JsonResponse
    {
        $agent = Agent::where('agent_code', $code)
            ->active()
            ->first();

        if (! $agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $agent->id,
                'agent_code' => $agent->agent_code,
                'name' => $agent->full_name,
                'display_name' => $agent->display_name,
                'email' => $agent->email,
                'phone' => $agent->phone,
                'type' => $agent->type,
                'region' => $agent->region,
                'commission_rate' => $agent->commission_rate,
            ],
        ]);
    }

    /**
     * Validate referral code
     */
    public function validateReferral(Request $request): JsonResponse
    {
        $request->validate([
            'referral_code' => 'required|string',
        ]);

        $referralLink = \App\Models\AgentReferralLink::where('code', $request->referral_code)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with('agent:id,agent_code,first_name,last_name,type')
            ->first();

        if (! $referralLink) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired referral code',
            ], 404);
        }

        // Increment click count
        $referralLink->increment('click_count');

        return response()->json([
            'success' => true,
            'data' => [
                'agent' => [
                    'id' => $referralLink->agent->id,
                    'code' => $referralLink->agent->agent_code,
                    'name' => $referralLink->agent->full_name,
                    'type' => $referralLink->agent->type,
                ],
                'campaign' => $referralLink->campaign_name,
            ],
        ]);
    }

    /**
     * Get agent types
     */
    public function getTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                ['value' => 'field', 'label' => 'Field Agent'],
                ['value' => 'online', 'label' => 'Online Agent'],
                ['value' => 'direct', 'label' => 'Direct Agent'],
            ],
        ]);
    }
}
