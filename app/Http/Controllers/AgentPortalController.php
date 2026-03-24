<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\Commission;
use App\Models\ApplicationState;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AgentPortalController extends Controller
{
    /**
     * Show agent login page
     */
    public function showLogin()
    {
        // If already logged in, redirect to dashboard
        if (Session::has('agent_code')) {
            return redirect()->route('agent.dashboard');
        }
        
        return Inertia::render('agent/Login', [
            'status' => session('success'),
            'error' => session('error'),
        ]);
    }
    
    /**
     * Handle agent login
     * Supports both:
     * - Existing agents from Agent model (physical agents with AGT codes)
     * - New agents from AgentApplication model (online agents with AG codes)
     */
    public function login(Request $request)
    {
        $request->validate([
            'agent_code' => 'required|string|min:5|max:15',
        ]);
        
        $agentCode = strtoupper(trim($request->agent_code));
        
        // First, try to find in Agent model (existing/seeded agents)
        $existingAgent = Agent::where('agent_code', $agentCode)
            ->where('status', 'active')
            ->first();
        
        if ($existingAgent) {
            // Store session for existing agent
            Session::put('agent_code', $agentCode);
            Session::put('agent_id', $existingAgent->id);
            Session::put('agent_name', $existingAgent->full_name);
            Session::put('agent_type', 'agent'); // From Agent model
            Session::put('agent_source', 'agents');
            
            return redirect()->route('agent.dashboard');
        }
        
        // Second, try to find in AgentApplication model (new WhatsApp agents)
        $applicationAgent = AgentApplication::where('agent_code', $agentCode)
            ->where('status', 'approved')
            ->first();
        
        if ($applicationAgent) {
            // Store session for application agent
            Session::put('agent_code', $agentCode);
            Session::put('agent_id', $applicationAgent->id);
            Session::put('agent_name', $applicationAgent->first_name . ' ' . $applicationAgent->surname);
            Session::put('agent_type', 'application'); // From AgentApplication model
            Session::put('agent_source', 'agent_applications');
            
            return redirect()->route('agent.dashboard');
        }
        
        // No valid agent found
        return back()->withErrors([
            'agent_code' => 'Invalid agent code or your account is not active.',
        ])->withInput();
    }
    
    /**
     * Show agent dashboard
     */
    public function dashboard()
    {
        if (!Session::has('agent_code')) {
            return redirect()->route('agent.login');
        }
        
        $agentCode = Session::get('agent_code');
        $agentSource = Session::get('agent_source', 'agent_applications');
        
        $agentModel = null;
        $agentData = null;
        $agentId = null;
        
        if ($agentSource === 'agents') {
            $agentModel = Agent::where('agent_code', $agentCode)->first();
            if (!$agentModel) {
                return $this->forceLogout();
            }
            $agentId = $agentModel->id;
            
            $agentData = (object) [
                'id' => $agentModel->id,
                'first_name' => $agentModel->first_name,
                'surname' => $agentModel->last_name,
                'province' => $agentModel->region ?? 'N/A',
                'whatsapp_contact' => $agentModel->phone,
                'ecocash_number' => $agentModel->ecocash_number ?? 'N/A',
                'agent_code' => $agentModel->agent_code,
                'referral_link' => config('app.url') . '/apply?ref=' . $agentModel->agent_code,
                'created_at' => $agentModel->created_at,
                'updated_at' => $agentModel->updated_at,
                'supervisor_comment' => $agentModel->supervisor_comment,
            ];
        } else {
            $agentModel = AgentApplication::where('agent_code', $agentCode)->first();
            if (!$agentModel) {
                return $this->forceLogout();
            }
            $agentData = (object) [
                'id' => $agentModel->id,
                'first_name' => $agentModel->first_name,
                'surname' => $agentModel->surname,
                'province' => $agentModel->province ?? 'N/A',
                'whatsapp_contact' => $agentModel->whatsapp_contact,
                'ecocash_number' => $agentModel->ecocash_number ?? 'N/A',
                'agent_code' => $agentModel->agent_code,
                'referral_link' => $agentModel->referral_link,
                'created_at' => $agentModel->created_at,
                'updated_at' => $agentModel->updated_at,
                'supervisor_comment' => $agentModel->supervisor_comment,
            ];
        }

        // Base query for applications referred by this agent code
        $referralQuery = ApplicationState::where(function($q) use ($agentCode, $agentId) {
            $q->whereJsonContains('form_data->referralCode', $agentCode)
              ->orWhereJsonContains('form_data->agentCode', $agentCode);
            if ($agentId) {
                $q->orWhere('agent_id', $agentId);
            }
        });

        // Split referrals by payment method
        $cashReferrals = (clone $referralQuery)
            ->where(function($q) {
                $q->whereJsonContains('form_data->formResponses->paymentMethod', 'cash')
                  ->orWhereJsonContains('form_data->paymentMethod', 'cash');
            })->count();

        $creditReferrals = (clone $referralQuery)
            ->where(function($q) {
                $q->whereJsonContains('form_data->formResponses->paymentMethod', 'credit')
                  ->orWhereJsonContains('form_data->paymentMethod', 'credit')
                  ->orWhereJsonContains('form_data->formResponses->paymentMethod', 'hire_purchase')
                  ->orWhereJsonContains('form_data->paymentMethod', 'hire_purchase');
            })->count();

        // Successful (approved) referrals
        $successfulReferrals = (clone $referralQuery)
            ->where('status', 'approved')
            ->count();

        // Commission data (only if agent_id from Agent model exists)
        $lastCommission = $agentId ? Commission::where('agent_id', $agentId)->latest('earned_date')->first() : null;
        $totalEarned = $agentId ? Commission::where('agent_id', $agentId)->where('status', 'paid')->sum('amount') : 0;
        $pendingCommission = $agentId ? Commission::where('agent_id', $agentId)->whereIn('status', ['pending', 'approved'])->sum('amount') : 0;

        // Load product categories for link generator
        $categories = ProductCategory::with(['subCategories' => function($q) {
            $q->with('products:id,product_sub_category_id,name,image_url');
        }])->get();

        // Aggregate monthly performance (last 6 months)
        $monthlyPerformance = $this->getMonthlyPerformance($agentCode, $agentId);

        return Inertia::render('agent/Dashboard', [
            'agent' => $agentData,
            'stats' => [
                'total_referrals' => $referralQuery->count(),
                'successful_referrals' => $successfulReferrals,
                'pending_commission' => (float) $pendingCommission,
                'total_earned' => (float) $totalEarned,
            ],
            'lastCommissionDate' => $lastCommission?->earned_date?->format('Y-m-d'),
            'cashReferrals' => $cashReferrals,
            'creditReferrals' => $creditReferrals,
            'supervisorComment' => $agentData->supervisor_comment,
            'productCategories' => $categories,
            'monthlyPerformance' => $monthlyPerformance,
        ]);
    }

    /**
     * Generate a product-specific referral link
     */
    public function generateProductLink(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $agentCode = Session::get('agent_code');
        $referralLink = config('app.url') . '/apply?ref=' . $agentCode . '&product_id=' . $request->product_id;

        return response()->json([
            'link' => $referralLink,
        ]);
    }

    /**
     * Get monthly performance data for charts
     */
    private function getMonthlyPerformance($agentCode, $agentId)
    {
        $data = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthYear = $date->format('M Y');
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            // Real referral count for this month
            $referrals = ApplicationState::where(function($q) use ($agentCode, $agentId) {
                $q->whereJsonContains('form_data->referralCode', $agentCode)
                  ->orWhereJsonContains('form_data->agentCode', $agentCode);
                if ($agentId) {
                    $q->orWhere('agent_id', $agentId);
                }
            })
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

            // Visits are mocked as we don't have a click log table with dates
            $data->push([
                'month' => $monthYear,
                'visits' => $referrals > 0 ? rand($referrals * 5, $referrals * 15) : rand(10, 30),
                'referrals' => $referrals,
            ]);
        }

        return $data;
    }

    /**
     * Force logout and redirect
     */
    private function forceLogout()
    {
        Session::forget(['agent_code', 'agent_id', 'agent_name', 'agent_type', 'agent_source']);
        return redirect()->route('agent.login')->with('error', 'Session expired. Please login again.');
    }
    
    /**
     * Logout agent
     */
    public function logout()
    {
        Session::forget(['agent_code', 'agent_id', 'agent_name', 'agent_type', 'agent_source']);
        return redirect()->route('agent.login')->with('success', 'You have been logged out successfully.');
    }
    
    /**
     * API: Validate agent code
     */
    public function validateAgentCode(Request $request)
    {
        $request->validate([
            'agent_code' => 'required|string',
        ]);
        
        $agentCode = strtoupper($request->agent_code);
        
        // Check Agent model first
        $existingAgent = Agent::where('agent_code', $agentCode)
            ->where('status', 'active')
            ->first();
        
        if ($existingAgent) {
            return response()->json([
                'valid' => true,
                'agent_name' => $existingAgent->full_name,
                'referral_link' => config('app.url') . '/apply?ref=' . $existingAgent->agent_code,
            ]);
        }
        
        // Check AgentApplication model
        $applicationAgent = AgentApplication::where('agent_code', $agentCode)
            ->where('status', 'approved')
            ->first();
        
        if ($applicationAgent) {
            return response()->json([
                'valid' => true,
                'agent_name' => $applicationAgent->first_name . ' ' . $applicationAgent->surname,
                'referral_link' => $applicationAgent->referral_link,
            ]);
        }
        
        return response()->json([
            'valid' => false,
            'message' => 'Invalid or inactive agent code',
        ], 404);
    }
}
