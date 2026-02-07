<?php

namespace App\Http\Controllers;

use App\Models\ApplicationState;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Show the welcome page
     */
    public function index(Request $request): Response
    {
        $hasApplications = false;
        $hasCompletedApplications = false;

        // Check if user is authenticated and has any applications
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user has any applications (using email, phone number, or national ID)
            if ($user->email || $user->phone || $user->national_id) {
                // Check for ApplicationState records
                $applicationQuery = ApplicationState::query();
                $applicationQuery->where(function($subQuery) use ($user) {
                    // Check user_identifier field
                    if ($user->email) {
                        $subQuery->where('user_identifier', $user->email);
                    }
                    if ($user->phone) {
                        $subQuery->orWhere('user_identifier', $user->phone);
                    }
                    if ($user->national_id) {
                        $subQuery->orWhere('user_identifier', $user->national_id);
                    }

                    // Also check the JSON form_data field for National ID
                    if ($user->national_id) {
                        $subQuery->orWhereJsonContains('form_data->formResponses->nationalIdNumber', $user->national_id)
                                 ->orWhereJsonContains('form_data->formResponses->idNumber', $user->national_id)
                                 ->orWhereJsonContains('form_data->formResponses->nationalId', $user->national_id);
                        
                        // Also check for unformatted National ID (no dashes/spaces)
                        $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $user->national_id);
                        if ($cleanId && $cleanId !== $user->national_id) {
                            $subQuery->orWhere('user_identifier', $cleanId)
                                     ->orWhere('user_identifier', 'id_' . $cleanId)
                                     ->orWhereJsonContains('form_data->formResponses->nationalIdNumber', $cleanId)
                                     ->orWhereJsonContains('form_data->formResponses->idNumber', $cleanId)
                                     ->orWhereJsonContains('form_data->formResponses->nationalId', $cleanId);
                        }
                    }

                    // Check email in JSON form_data
                    if ($user->email) {
                        $subQuery->orWhereJsonContains('form_data->formResponses->emailAddress', $user->email)
                                 ->orWhereJsonContains('form_data->formResponses->email', $user->email);
                    }

                    // Check phone in JSON form_data
                    if ($user->phone) {
                        $subQuery->orWhereJsonContains('form_data->formResponses->mobile', $user->phone)
                                 ->orWhereJsonContains('form_data->formResponses->phone', $user->phone);
                    }
                });

                $hasApplications = $applicationQuery->exists();

                // Check if user has any COMPLETED applications (submitted, not just in-progress)
                if ($hasApplications) {
                    $hasCompletedApplications = ApplicationState::where(function($query) use ($user) {
                             $query->where('user_identifier', $user->email)
                                   ->orWhere('user_identifier', $user->phone)
                                   ->orWhere('user_identifier', $user->national_id);
                             
                             if ($user->national_id) {
                                 $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $user->national_id);
                                 if ($cleanId) {
                                     $query->orWhere('user_identifier', $cleanId);
                                 }
                             }
                        })
                        ->whereIn('current_step', ['completed', 'approved', 'in_review', 'processing', 'pending_documents'])
                        ->exists();
                }
            }
        }

        // Retrieve referral data from session
        $referralCode = session('referral_code');
        $agentId = session('agent_id');
        $agentName = session('agent_name');

        return Inertia::render('welcome', [
            'hasApplications' => $hasApplications,
            'hasCompletedApplications' => $hasCompletedApplications,
            'referralCode' => $referralCode,
            'agentId' => $agentId,
            'agentName' => $agentName,
        ]);
    }
}