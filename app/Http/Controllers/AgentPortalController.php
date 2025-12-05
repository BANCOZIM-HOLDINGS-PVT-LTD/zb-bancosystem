<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AgentApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
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
        
        $agent = null;
        $stats = [
            'total_referrals' => 0,
            'successful_referrals' => 0,
            'pending_commission' => 0,
            'total_earned' => 0,
        ];
        
        if ($agentSource === 'agents') {
            // Existing agent from Agent model
            $existingAgent = Agent::where('agent_code', $agentCode)->first();
            
            if (!$existingAgent) {
                Session::forget(['agent_code', 'agent_id', 'agent_name', 'agent_type', 'agent_source']);
                return redirect()->route('agent.login')->with('error', 'Session expired. Please login again.');
            }
            
            // Map to standard format for dashboard
            $agent = (object) [
                'id' => $existingAgent->id,
                'first_name' => $existingAgent->first_name,
                'surname' => $existingAgent->last_name,
                'province' => $existingAgent->region ?? 'N/A',
                'whatsapp_contact' => $existingAgent->phone,
                'ecocash_number' => $existingAgent->ecocash_number ?? 'N/A',
                'agent_code' => $existingAgent->agent_code,
                'referral_link' => config('app.url') . '/apply?ref=' . $existingAgent->agent_code,
                'created_at' => $existingAgent->created_at,
                'updated_at' => $existingAgent->updated_at,
            ];
            
            // Get real stats from Agent model
            $stats = [
                'total_referrals' => $existingAgent->total_applications ?? 0,
                'successful_referrals' => $existingAgent->approved_applications ?? 0,
                'pending_commission' => $existingAgent->pending_commission ?? 0,
                'total_earned' => $existingAgent->total_commission_earned ?? 0,
            ];
            
        } else {
            // New agent from AgentApplication model
            $applicationAgent = AgentApplication::where('agent_code', $agentCode)->first();
            
            if (!$applicationAgent) {
                Session::forget(['agent_code', 'agent_id', 'agent_name', 'agent_type', 'agent_source']);
                return redirect()->route('agent.login')->with('error', 'Session expired. Please login again.');
            }
            
            $agent = $applicationAgent;
        }
        
        return Inertia::render('agent/Dashboard', [
            'agent' => $agent,
            'stats' => $stats,
        ]);
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
