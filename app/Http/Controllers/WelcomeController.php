<?php

namespace App\Http\Controllers;

use App\Models\ApplicationState;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Show the welcome page
     */
    public function index(): Response
    {
        $hasApplications = false;

        // Check if user is authenticated and has any applications
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user has any applications (using email, phone number, or national ID)
            $query = ApplicationState::query();

            if ($user->email || $user->phone || $user->national_id) {
                $query->where(function ($subQuery) use ($user) {
                    if ($user->email) {
                        $subQuery->where('user_identifier', $user->email);
                    }
                    if ($user->phone) {
                        $subQuery->orWhere('user_identifier', $user->phone);
                    }
                    if ($user->national_id) {
                        $subQuery->orWhere('user_identifier', $user->national_id);
                    }
                });

                $hasApplications = $query->exists();
            }
        }

        return Inertia::render('welcome', [
            'hasApplications' => $hasApplications,
        ]);
    }
}
