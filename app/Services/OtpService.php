<?php

namespace App\Services;

use App\Models\User;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected $twilio;
    protected $fromNumber;

    public function __construct()
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        // Use alpha sender ID for branding (e.g., Microbiz), fallback to phone number
        $this->fromNumber = config('services.twilio.alpha_sender_id') ?: config('services.twilio.from');

        // Use Account SID and Auth Token for authentication
        $this->twilio = new Client($accountSid, $authToken);
    }

    /**
     * Send OTP to a phone number
     *
     * @param User $user
     * @return bool
     */
    public function sendOtp(User $user): bool
    {
        try {
            $otp = $user->generateOtp();

            $message = "Hello, welcome to Microbiz. Please copy this code {$otp} and enter it to complete your account verification. If you encounter any problem you may restart your application on bancosystem.fly.dev";

            // Dispatch job to send SMS asynchronously
            \App\Jobs\SendOtpJob::dispatch($user->phone, $message);

            Log::info('OTP queued for sending', [
                'user_id' => $user->id,
                'phone' => $user->phone,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue OTP', [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify OTP code
     *
     * @param User $user
     * @param string $otp
     * @return bool
     */
    public function verifyOtp(User $user, string $otp): bool
    {
        return $user->verifyOtp($otp);
    }

    /**
     * Resend OTP to user
     *
     * @param User $user
     * @return bool
     */
    public function resendOtp(User $user): bool
    {
        // Check if last OTP was sent less than 1 minute ago
        if ($user->otp_expires_at && $user->otp_expires_at->gt(now()->addMinutes(9))) {
            return false; // Too soon to resend
        }

        return $this->sendOtp($user);
    }
}