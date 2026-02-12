<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $phoneNumber;
    protected $message;
    protected $referenceCode;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phoneNumber, string $message, ?string $referenceCode = null)
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->referenceCode = $referenceCode;
    }

    /**
     * Execute the job.
     */
    public function handle(SmsProviderInterface $smsService): void
    {
        try {
            // Format phone number
            $formattedPhone = $smsService->formatPhoneNumber($this->phoneNumber);

            Log::info("Processing SendSmsJob", [
                'to' => $formattedPhone,
                'reference' => $this->referenceCode
            ]);

            // Send SMS
            $result = $smsService->sendSms($formattedPhone, $this->message);

            if (!$result['success']) {
                Log::error("SendSmsJob failed to send SMS", [
                    'to' => $formattedPhone,
                    'error' => $result['error'] ?? 'Unknown error',
                    'result' => $result
                ]);
                
                // Release the job back to the queue to retry after 60 seconds
                // validation errors in dev might just fail forever, but network issues should retry
                $this->release(60); 
            } else {
                Log::info("SendSmsJob completed successfully", [
                    'to' => $formattedPhone,
                    'message_sid' => $result['message_sid'] ?? null
                ]);
            }

        } catch (\Exception $e) {
            Log::error("SendSmsJob exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->fail($e);
        }
    }
}
