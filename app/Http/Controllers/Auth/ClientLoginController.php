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

        // Redirect to home page - will show tracking options for returning users
        return redirect()->intended(route('home', absolute: false));
    }
}