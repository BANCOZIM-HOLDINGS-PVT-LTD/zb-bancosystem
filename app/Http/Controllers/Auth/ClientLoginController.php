<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientLoginController extends Controller
{

    /**
     * Show the client login page
     */
    public function create(): Response
    {
        return Inertia::render('auth/client-login');
    }

    /**
     * Handle the client login request
     */
    public function store(Request $request)
    {
        $request->validate([
            'national_id' => [
                'required',
                'string',
                'regex:/^[0-9]{2}-[0-9]{6,7}-[A-Z]-[0-9]{2}$/', // Zimbabwe National ID format: XX-XXXXXXX-Y-XX
            ],
        ], [
            'national_id.regex' => 'Please enter a valid Zimbabwe National ID (e.g., 08-2047823-Q-29)',
        ]);

        $user = User::where('national_id', $request->national_id)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'national_id' => 'No account found with this National ID. Please register first.',
            ]);
        }

        // Log the user in directly with National ID only
        Auth::login($user);

        // Check if the user has an existing application
        // We need to check both formatted and unformatted ID, and also email/phone
        $nationalId = $user->national_id;
        $cleanId = preg_replace('/[^a-zA-Z0-9]/', '', $nationalId);
        $email = $user->email;
        $phone = $user->phone;

        $hasApplication = \App\Models\ApplicationState::where(function ($query) use ($nationalId, $cleanId, $email, $phone) {
            // Check Reference Code (Clean ID)
            $query->where('reference_code', $cleanId);

            // Check User Identifier (could be ID, Email, or Phone)
            $query->orWhere('user_identifier', $nationalId)
                  ->orWhere('user_identifier', $cleanId);
            
            if ($email) {
                $query->orWhere('user_identifier', $email);
            }
            if ($phone) {
                $query->orWhere('user_identifier', $phone);
            }

            // Check JSON Data for National ID (various keys)
            $query->orWhereJsonContains('form_data->formResponses->nationalIdNumber', $nationalId)
                  ->orWhereJsonContains('form_data->formResponses->idNumber', $nationalId)
                  ->orWhereJsonContains('form_data->formResponses->nationalId', $nationalId)
                  ->orWhereJsonContains('form_data->formResponses->nationalIdNumber', $cleanId)
                  ->orWhereJsonContains('form_data->formResponses->idNumber', $cleanId)
                  ->orWhereJsonContains('form_data->formResponses->nationalId', $cleanId);
            
            // Check JSON Data for Email and Phone
            if ($email) {
                $query->orWhereJsonContains('form_data->formResponses->emailAddress', $email)
                      ->orWhereJsonContains('form_data->formResponses->email', $email);
            }
            if ($phone) {
                $query->orWhereJsonContains('form_data->formResponses->mobile', $phone)
                      ->orWhereJsonContains('form_data->formResponses->phone', $phone);
            }
        })->exists();

        if ($hasApplication) {
            // Redirect to home page (welcome page) which will show tracking/status options
            return redirect()->intended(route('home', absolute: false));
        }

        // Redirect to home page (welcome page) for new applications
        return redirect()->intended(route('home', absolute: false));
    }
}