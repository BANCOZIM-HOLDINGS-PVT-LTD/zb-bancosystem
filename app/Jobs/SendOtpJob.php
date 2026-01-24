<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class SendOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;
    protected $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phoneNumber, string $message)
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $accountSid = config('services.twilio.account_sid');
            $authToken = config('services.twilio.auth_token');
            $fromNumber = config('services.twilio.alpha_sender_id') ?: config('services.twilio.from');

            $twilio = new Client($accountSid, $authToken);

            $twilio->messages->create(
                $this->phoneNumber,
                [
                    'from' => $fromNumber,
                    'body' => $this->message
                ]
            );

            Log::info('OTP SMS sent via queue', [
                'phone' => $this->phoneNumber,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send queued OTP SMS', [
                'phone' => $this->phoneNumber,
                'error' => $e->getMessage(),
            ]);
            
            // Release the job back to the queue to try again later if it failed
            // $this->release(10); 
        }
    }
}
