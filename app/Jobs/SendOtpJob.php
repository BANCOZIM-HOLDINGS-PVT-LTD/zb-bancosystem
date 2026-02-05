<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\TwilioSmsService;
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
            $smsService = new TwilioSmsService();
            
            // Format number to E.164 (e.g. +263...) to ensure delivery
            $formattedPhone = $smsService->formatPhoneNumber($this->phoneNumber);
            
            $smsService->sendSms($formattedPhone, $this->message);

            Log::info('OTP SMS sent via queue', [
                'original_phone' => $this->phoneNumber,
                'formatted_phone' => $formattedPhone,
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
