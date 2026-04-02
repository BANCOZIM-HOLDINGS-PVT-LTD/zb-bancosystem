<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\Commission;
use App\Models\ApplicationState;
use App\Models\ProductCategory;
use App\Models\AgentActivityLog;
use App\Models\AgentReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

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
            
            // Update last activity
            $agentModel->update(['last_activity_at' => now()]);
            
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
            
            // Update last activity
            $agentModel->update(['last_activity_at' => now()]);

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
        
        // PENDING COMMISSION should be for the current month
        $startOfMonth = now()->startOfMonth();
        $pendingCommission = $agentId ? Commission::where('agent_id', $agentId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('earned_date', '>=', $startOfMonth)
            ->sum('amount') : 0;

        // Load product categories for link generator
        $categories = ProductCategory::with(['subCategories' => function($q) {
            $q->with('products:id,product_sub_category_id,name,image_url');
        }])->get();

        // General Links with Posters (5 distinct ones)
        $defaultPoster = '/Product-Image-Coming-Soon.png';
        $generalLinks = [
            ['id' => 1, 'name' => 'General Campaign', 'poster' => $defaultPoster, 'description' => 'Promote all our products with this general link.'],
            ['id' => 2, 'name' => 'Home & Living', 'poster' => $defaultPoster, 'description' => 'Focus on furniture and home appliances.'],
            ['id' => 3, 'name' => 'Tech & Gadgets', 'poster' => $defaultPoster, 'description' => 'Promote our latest technology and smartphones.'],
            ['id' => 4, 'name' => 'Solar Solutions', 'poster' => $defaultPoster, 'description' => 'Go green with our solar energy packages.'],
            ['id' => 5, 'name' => 'Financial Services', 'poster' => $defaultPoster, 'description' => 'Refer clients for account opening and cash loans.'],
        ];

        // Aggregate monthly performance (last 6 months)
        $monthlyPerformance = $this->getMonthlyPerformance($agentCode, $agentId);

        // Product application history with status (Showing all products applied for)
        $applicationHistory = (clone $referralQuery)
            ->select('id', 'created_at', 'status', 'form_data', 'reference_code')
            ->latest()
            ->limit(50)
            ->get()
            ->map(function($app) use ($agentModel) {
                $formData = $app->form_data ?? [];
                $commission = 0;
                $tier = $agentModel->tier ?? 'ordinary';
                
                if ($app->status === 'approved') {
                    // Check if there is an actual commission record
                    $actualCommission = Commission::where('application_id', $app->id)->first();
                    if ($actualCommission) {
                        $commission = $actualCommission->amount;
                    } else {
                        $loanAmount = floatval($formData['formResponses']['loanAmount'] ?? ($formData['loanAmount'] ?? 0));
                        $rate = ($tier === 'higher_achiever' ? 1.5 : 1.0) / 100;
                        $commission = round($loanAmount * $rate, 2);
                    }
                }
                
                return [
                    'id' => $app->id,
                    'date' => $app->created_at->format('Y-m-d H:i'),
                    'product' => $formData['productName'] ?? ($formData['selectedBusiness']['name'] ?? 'General Application'),
                    'status' => $app->status,
                    'commission' => (float)$commission,
                    'reference' => $app->reference_code ?? ('#'.str_pad($app->id, 5, '0', STR_PAD_LEFT)),
                ];
            });

        // Activity Log
        $activityLogs = AgentActivityLog::where('agent_id', $agentId ?: $agentModel->id)
            ->where('agent_type', $agentSource === 'agents' ? 'agents' : 'agent_applications')
            ->latest()
            ->limit(30)
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->activity_type,
                    'description' => $log->description,
                    'timestamp' => $log->created_at->format('M d, H:i'),
                ];
            });

        // Milestones
        $startOfWeek = now()->startOfWeek();
        $weeklyCommission = $agentId ? Commission::where('agent_id', $agentId)
            ->where('earned_date', '>=', $startOfWeek)
            ->sum('amount') : 0;
        
        // If they are higher achiever but didn't hit target last week, we might need logic to demote,
        // but for now we just show current week progress.
        
        $dailyReferrals = (clone $referralQuery)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        // Check if agent is deactivated
        $isDeactivated = (bool)($agentModel->is_deactivated ?? false);
        
        // Simple deactivation check: if last_activity_at > 30 days
        if (!$isDeactivated && $agentModel->last_activity_at && $agentModel->last_activity_at->diffInDays(now()) > 30) {
            $agentModel->update([
                'is_deactivated' => true,
                'deactivated_at' => now(),
            ]);
            $isDeactivated = true;

            AgentActivityLog::create([
                'agent_id' => $agentModel->id,
                'agent_type' => $agentSource,
                'activity_type' => 'deactivation',
                'description' => 'Account automatically deactivated due to 30 days of inactivity.',
            ]);
        }

        return Inertia::render('agent/Dashboard', [
            'agent' => array_merge((array)$agentData, [
                'tier' => $agentModel->tier ?? 'ordinary',
                'last_commission_amount' => (float)($agentModel->last_commission_amount ?? 0),
                'is_deactivated' => $isDeactivated,
                'deactivated_at' => $agentModel->deactivated_at ? $agentModel->deactivated_at->format('Y-m-d H:i') : null,
            ]),
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
            'generalLinks' => $generalLinks,
            'monthlyPerformance' => $monthlyPerformance,
            'applicationHistory' => $applicationHistory,
            'activityLogs' => $activityLogs,
            'milestones' => [
                'weekly_commission' => (float)$weeklyCommission,
                'weekly_target' => 150.0,
                'daily_referrals' => $dailyReferrals,
                'daily_target' => 20,
            ]
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
        $agentSource = Session::get('agent_source');
        $agentId = Session::get('agent_id');

        $referralLink = config('app.url') . '/apply?ref=' . $agentCode . '&product_id=' . $request->product_id;

        // Log Activity
        AgentActivityLog::create([
            'agent_id' => $agentId,
            'agent_type' => $agentSource,
            'activity_type' => 'link_generation',
            'description' => 'Generated a product referral link for product ID: ' . $request->product_id,
        ]);

        // Update last activity on agent model
        if ($agentSource === 'agents') {
            Agent::where('id', $agentId)->update(['last_activity_at' => now()]);
        } else {
            AgentApplication::where('id', $agentId)->update(['last_activity_at' => now()]);
        }

        return response()->json([
            'link' => $referralLink,
        ]);
    }

    /**
     * Log an activity from the frontend (e.g. copying a link)
     */
    public function logActivity(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'description' => 'required|string',
        ]);

        $agentCode = Session::get('agent_code');
        $agentSource = Session::get('agent_source');
        $agentId = Session::get('agent_id');

        if (!$agentCode) return response()->json(['error' => 'Not authenticated'], 401);

        AgentActivityLog::create([
            'agent_id' => $agentId,
            'agent_type' => $agentSource,
            'activity_type' => $request->type,
            'description' => $request->description,
        ]);

        // Update last activity
        if ($agentSource === 'agents') {
            Agent::where('id', $agentId)->update(['last_activity_at' => now()]);
        } else {
            AgentApplication::where('id', $agentId)->update(['last_activity_at' => now()]);
        }

        return response()->json(['success' => true]);
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
     * Reactivate agent account
     */
    public function reactivate(Request $request)
    {
        $agentCode = Session::get('agent_code');
        if (!$agentCode) return back();

        $agentSource = Session::get('agent_source');
        $agent = $agentSource === 'agents' 
            ? Agent::where('agent_code', $agentCode)->first()
            : AgentApplication::where('agent_code', $agentCode)->first();

        if ($agent && $agent->is_deactivated) {
            // Logic: reactivation happens after 24 hours (simulated or scheduled)
            // For now, we'll mark it as pending reactivation in metadata
            $metadata = $agent->metadata ?? [];
            $metadata['reactivation_requested_at'] = now()->toISOString();
            $agent->metadata = $metadata;
            $agent->save();

            AgentActivityLog::create([
                'agent_id' => $agent->id,
                'agent_type' => $agentSource,
                'activity_type' => 'reactivation_requested',
                'description' => 'Agent requested account reactivation.',
            ]);

            return back()->with('success', 'Reactivation requested. Your account will be active within 24 hours.');
        }

        return back();
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
