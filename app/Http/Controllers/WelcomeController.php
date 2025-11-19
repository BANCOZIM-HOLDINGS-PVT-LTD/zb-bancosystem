<?php

namespace App\Http\Controllers;

use App\Models\ApplicationState;
use App\Models\CashPurchase;
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

        // Check if user is authenticated and has any applications or cash purchases
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
                    $hasCompletedApplications = $applicationQuery
                        ->where('current_step', 'documents')
                        ->where('is_completed', true)
                        ->exists();
                }

                // If no applications found, check for cash purchases
                if (!$hasApplications && $user->national_id) {
                    $hasCashPurchases = CashPurchase::where('national_id', $user->national_id)->exists();
                    $hasApplications = $hasCashPurchases;
                    $hasCompletedApplications = $hasCashPurchases; // Cash purchases are considered completed
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