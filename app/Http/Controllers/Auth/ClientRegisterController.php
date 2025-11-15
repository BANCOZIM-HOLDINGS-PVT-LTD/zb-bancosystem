<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientRegisterController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Show the client registration page
     */
    public function create(): Response
    {
        return Inertia::render('auth/client-register');
    }

    /**
     * Handle the client registration request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => [
                'required',
                'string',
                'regex:/^[0-9]{2}-[0-9]{6,7}-[A-Z]-[0-9]{2}$/', // Zimbabwe National ID format: XX-XXXXXXX-Y-XX
                'unique:users,national_id'
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^\+263[0-9]{9}$/', // Zimbabwe phone format: +263771234567
                'unique:users,phone'
            ],
        ], [
            'national_id.regex' => 'Please enter a valid Zimbabwe National ID (e.g., 08-2047823-Q-29)',
            'national_id.unique' => 'This National ID is already registered',
            'phone.regex' => 'Please enter a valid Zimbabwe phone number (e.g., +263771234567)',
            'phone.unique' => 'This phone number is already registered',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        // Create user without password (will be set during OTP verification)
        $user = User::create([
            'national_id' => $request->national_id,
            'phone' => $request->phone,
            'name' => '', // Will be updated later during application
        ]);

        // Send OTP
        $otpSent = $this->otpService->sendOtp($user);

        if (!$otpSent) {
            $user->delete(); // Clean up if OTP sending fails
            return back()->withErrors(['phone' => 'Failed to send OTP. Please try again.']);
        }

        // Store user ID in session for OTP verification
        session(['pending_user_id' => $user->id]);

        return redirect()->route('client.otp.verify');
    }

    /**
     * Show the OTP verification page
     */
    public function showOtpForm(): Response
    {
        $userId = session('pending_user_id');

        if (!$userId) {
            return redirect()->route('client.register');
        }

        $user = User::find($userId);

        if (!$user) {
            session()->forget('pending_user_id');
            return redirect()->route('client.register');
        }

        return Inertia::render('auth/verify-otp', [
            'phone' => $user->phone,
            'maskedPhone' => $this->maskPhone($user->phone),
        ]);
    }

    /**
     * Verify the OTP code
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $userId = session('pending_user_id');

        if (!$userId) {
            return back()->withErrors(['otp' => 'Session expired. Please register again.']);
        }

        $user = User::find($userId);

        if (!$user) {
            session()->forget('pending_user_id');
            return back()->withErrors(['otp' => 'User not found. Please register again.']);
        }

        $verified = $this->otpService->verifyOtp($user, $request->otp);

        if (!$verified) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP code. Please try again.']);
        }

        // OTP verified successfully, log the user in
        Auth::login($user);
        session()->forget('pending_user_id');

        // Redirect to home page - will show application options for new users
        return redirect()->route('home');
    }

    /**
     * Resend OTP code
     */
    public function resendOtp(Request $request)
    {
        $userId = session('pending_user_id');

        if (!$userId) {
            return response()->json(['message' => 'Session expired. Please register again.'], 400);
        }

        $user = User::find($userId);

        if (!$user) {
            session()->forget('pending_user_id');
            return response()->json(['message' => 'User not found. Please register again.'], 400);
        }

        $otpSent = $this->otpService->resendOtp($user);

        if (!$otpSent) {
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
            return substr($phone, 0, 4) . '****' . substr($phone, -4);
        }

        return $phone;
    }
}