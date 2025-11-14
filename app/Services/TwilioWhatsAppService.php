<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioWhatsAppService
{
    private $client;

    private $from;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );
        $this->from = config('services.twilio.whatsapp_from');
    }

    /**
     * Send a WhatsApp message
     */
    public function sendMessage(string $to, string $message): bool
    {
        try {
            $this->client->messages->create(
                $to, // To WhatsApp number (format: whatsapp:+1234567890)
                [
                    'from' => $this->from,
                    'body' => $message,
                ]
            );

            Log::info("WhatsApp message sent to {$to}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp message to {$to}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Send a WhatsApp message with media
     */
    public function sendMessageWithMedia(string $to, string $message, array $mediaUrls = []): bool
    {
        try {
            $messageData = [
                'from' => $this->from,
                'body' => $message,
            ];

            // Add media URLs if provided
            if (! empty($mediaUrls)) {
                $messageData['mediaUrl'] = $mediaUrls;
            }

            $this->client->messages->create($to, $messageData);

            Log::info("WhatsApp message with media sent to {$to}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp message with media to {$to}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Send interactive buttons (for simple choices)
     */
    public function sendButtonMessage(string $to, string $message, array $buttons): bool
    {
        try {
            // For now, we'll send a simple message with numbered options
            // Twilio's WhatsApp API has limitations on interactive messages
            $buttonText = "\n\n";
            foreach ($buttons as $index => $button) {
                $buttonText .= ($index + 1).'. '.$button."\n";
            }

            $fullMessage = $message.$buttonText."\nReply with the number of your choice.";

            return $this->sendMessage($to, $fullMessage);
        } catch (\Exception $e) {
            Log::error("Failed to send button message to {$to}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Format phone number for WhatsApp
     */
    public static function formatWhatsAppNumber(string $phoneNumber): string
    {
        // Remove any existing whatsapp: prefix
        $phoneNumber = str_replace('whatsapp:', '', $phoneNumber);

        // Ensure it starts with +
        if (! str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+'.$phoneNumber;
        }

        return 'whatsapp:'.$phoneNumber;
    }

    /**
     * Extract phone number from WhatsApp format
     */
    public static function extractPhoneNumber(string $whatsappNumber): string
    {
        return str_replace(['whatsapp:', '+'], '', $whatsappNumber);
    }
}
