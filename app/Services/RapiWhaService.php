<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RapiWhaService
{
    private string $apiKey;
    private string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.rapiwha.api_key');
        $this->apiUrl = config('services.rapiwha.api_url', 'https://panel.rapiwha.com');
    }

    /**
     * Send a WhatsApp text message
     *
     * @param string $to Phone number (format: whatsapp:+1234567890 or +1234567890 or 1234567890)
     * @param string $message Message text
     * @return bool Success status
     */
    public function sendMessage(string $to, string $message): bool
    {
        try {
            $phoneNumber = self::extractPhoneNumber($to);
            
            $response = Http::asForm()->post("{$this->apiUrl}/send_message.php", [
                'apikey' => $this->apiKey,
                'number' => $phoneNumber,
                'text' => $message,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['success']) && $result['success']) {
                    Log::info("RapiWha message sent to {$phoneNumber}");
                    return true;
                }
                
                // Some APIs return different success indicators
                if (isset($result['status']) && $result['status'] === 'success') {
                    Log::info("RapiWha message sent to {$phoneNumber}");
                    return true;
                }
                
                // If response is successful HTTP status, assume success
                Log::info("RapiWha message sent to {$phoneNumber}", ['response' => $result]);
                return true;
            }

            Log::error("Failed to send RapiWha message to {$phoneNumber}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error("RapiWha send message exception: " . $e->getMessage(), [
                'to' => $to,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send a WhatsApp message with media
     *
     * @param string $to Phone number
     * @param string $message Message text
     * @param array $mediaUrls Array of media URLs
     * @return bool Success status
     */
    public function sendMessageWithMedia(string $to, string $message, array $mediaUrls = []): bool
    {
        try {
            $phoneNumber = self::extractPhoneNumber($to);
            
            // Send text message first
            if (!empty($message)) {
                $this->sendMessage($to, $message);
            }

            // Send each media file separately (RapiWha typically requires separate requests)
            foreach ($mediaUrls as $mediaUrl) {
                $response = Http::asForm()->post("{$this->apiUrl}/send_media.php", [
                    'apikey' => $this->apiKey,
                    'number' => $phoneNumber,
                    'url' => $mediaUrl,
                ]);

                if (!$response->successful()) {
                    Log::warning("Failed to send media to {$phoneNumber}", [
                        'url' => $mediaUrl,
                        'status' => $response->status()
                    ]);
                }
            }

            Log::info("RapiWha message with media sent to {$phoneNumber}");
            return true;

        } catch (\Exception $e) {
            Log::error("RapiWha send media exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send interactive buttons (formatted as numbered options)
     *
     * @param string $to Phone number
     * @param string $message Message text
     * @param array $buttons Array of button labels
     * @return bool Success status
     */
    public function sendButtonMessage(string $to, string $message, array $buttons): bool
    {
        // Format buttons as numbered options since WhatsApp Web API
        // doesn't support interactive buttons directly
        $buttonText = "\n\n";
        foreach ($buttons as $index => $button) {
            $buttonText .= ($index + 1) . ". " . $button . "\n";
        }
        
        $fullMessage = $message . $buttonText . "\nReply with the number of your choice.";
        
        return $this->sendMessage($to, $fullMessage);
    }

    /**
     * Format phone number for WhatsApp
     *
     * @param string $phoneNumber Raw phone number
     * @return string Formatted as whatsapp:+number
     */
    public static function formatWhatsAppNumber(string $phoneNumber): string
    {
        // Remove any existing whatsapp: prefix
        $phoneNumber = str_replace('whatsapp:', '', $phoneNumber);
        
        // Remove spaces, dashes, parentheses
        $phoneNumber = preg_replace('/[\s\-\(\)]/', '', $phoneNumber);
        
        // Ensure it starts with +
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+' . $phoneNumber;
        }
        
        return 'whatsapp:' . $phoneNumber;
    }

    /**
     * Extract phone number from WhatsApp format
     *
     * @param string $whatsappNumber Number in format whatsapp:+1234567890
     * @return string Clean phone number (e.g., 1234567890)
     */
    public static function extractPhoneNumber(string $whatsappNumber): string
    {
        // Remove whatsapp: prefix and + sign
        $number = str_replace(['whatsapp:', '+'], '', $whatsappNumber);
        
        // Remove any remaining non-numeric characters
        return preg_replace('/[^0-9]/', '', $number);
    }

    /**
     * Check if the service is properly configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get the API key (for debugging, masked)
     *
     * @return string
     */
    public function getMaskedApiKey(): string
    {
        if (empty($this->apiKey)) {
            return '[NOT SET]';
        }
        return substr($this->apiKey, 0, 4) . '****' . substr($this->apiKey, -4);
    }
}
