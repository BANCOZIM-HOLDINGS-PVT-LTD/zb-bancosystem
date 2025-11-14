<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientLoginController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

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
                'regex:/^[0-9]{2}-[0-9]{6,7}[A-Z][0-9]{2}$/', // Zimbabwe National ID format
            ],
        ], [
            'national_id.regex' => 'Please enter a valid Zimbabwe National ID (e.g., 63-123456A12)',
        ]);

        $user = User::where('national_id', $request->national_id)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'national_id' => 'No account found with this National ID. Please register first.',
            ]);
        }

        // Log the user in directly without OTP
        Auth::login($user);

        // Redirect to home page - will show tracking options for returning users
        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Show the OTP verification page for login
     */
    public function showOtpForm(): Response
    {
        $userId = session('login_user_id');

        if (! $userId) {
            return redirect()->route('client.login');
        }

        $user = User::find($userId);

        if (! $user) {
            session()->forget('login_user_id');

            return redirect()->route('client.login');
        }

        return Inertia::render('auth/verify-login-otp', [
            'phone' => $user->phone,
            'maskedPhone' => $this->maskPhone($user->phone),
        ]);
    }

    /**
     * Verify the OTP code for login
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $userId = session('login_user_id');

        if (! $userId) {
            return back()->withErrors(['otp' => 'Session expired. Please login again.']);
        }

        $user = User::find($userId);

        if (! $user) {
            session()->forget('login_user_id');

            return back()->withErrors(['otp' => 'User not found. Please login again.']);
        }

        // For login, we just verify the OTP exists and is not expired
        if ($user->otp_code === $request->otp && $user->otp_expires_at && $user->otp_expires_at->isFuture()) {
            // Clear OTP
            $user->update([
                'otp_code' => null,
                'otp_expires_at' => null,
            ]);

            // Log the user in
            Auth::login($user);
            session()->forget('login_user_id');

            // Redirect to home page - will show tracking options for returning users
            return redirect()->intended(route('home', absolute: false));
        }

        return back()->withErrors(['otp' => 'Invalid or expired OTP code. Please try again.']);
    }

    /**
     * Resend OTP code for login
     */
    public function resendOtp(Request $request)
    {
        $userId = session('login_user_id');

        if (! $userId) {
            return response()->json(['message' => 'Session expired. Please login again.'], 400);
        }

        $user = User::find($userId);

        if (! $user) {
            session()->forget('login_user_id');

            return response()->json(['message' => 'User not found. Please login again.'], 400);
        }

        $otpSent = $this->otpService->resendOtp($user);

        if (! $otpSent) {
            return response()->json(['message' => 'Please wait before requesting a new code.'], 429);
        }

        return response()->json(['message' => 'OTP code sent successfully!']);
    }

    /**
     * Mask phone number for display
     */
    private function maskPhone(string $phone): string
    {
        // Convert +263771234567 to +263****4567
        if (strlen($phone) >= 8) {
            return substr($phone, 0, 4).'****'.substr($phone, -4);
        }

        return $phone;
    }
}
