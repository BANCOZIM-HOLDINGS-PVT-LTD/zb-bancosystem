<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Twilio WhatsApp Service
 * 
 * Handles sending WhatsApp messages via Twilio API
 */
class TwilioWhatsAppService
{
    protected ?Client $client = null;
    protected string $accountSid;
    protected string $authToken;
    protected string $fromNumber;
    protected string $whatsappBusinessId;
    
    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid', '');
        $this->authToken = config('services.twilio.auth_token', '');
        $this->fromNumber = config('services.twilio.whatsapp_from', '');
        $this->whatsappBusinessId = config('services.twilio.whatsapp_business_id', '');
    }
    
    /**
     * Get or create Twilio client
     */
    protected function getClient(): ?Client
    {
        if ($this->client === null && $this->isConfigured()) {
            try {
                $this->client = new Client($this->accountSid, $this->authToken);
            } catch (\Exception $e) {
                Log::error('Failed to create Twilio client: ' . $e->getMessage());
                return null;
            }
        }
        return $this->client;
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
            $client = $this->getClient();
            
            if (!$client) {
                Log::error('Twilio WhatsApp: Client not configured');
                return false;
            }
            
            $toNumber = self::formatWhatsAppNumber($to);
            
            $response = $client->messages->create(
                $toNumber,
                [
                    'from' => $this->fromNumber,
                    'body' => $message
                ]
            );
            
            Log::info("Twilio WhatsApp message sent", [
                'to' => $toNumber,
                'sid' => $response->sid,
                'status' => $response->status
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Twilio WhatsApp send message error: " . $e->getMessage(), [
                'to' => $to,
                'error' => $e->getMessage()
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
            $client = $this->getClient();
            
            if (!$client) {
                Log::error('Twilio WhatsApp: Client not configured');
                return false;
            }
            
            $toNumber = self::formatWhatsAppNumber($to);
            
            // Twilio supports up to 10 media URLs per message
            $params = [
                'from' => $this->fromNumber,
                'body' => $message
            ];
            
            if (!empty($mediaUrls)) {
                $params['mediaUrl'] = array_slice($mediaUrls, 0, 10);
            }
            
            $response = $client->messages->create($toNumber, $params);
            
            Log::info("Twilio WhatsApp message with media sent", [
                'to' => $toNumber,
                'sid' => $response->sid,
                'media_count' => count($mediaUrls)
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Twilio WhatsApp send media error: " . $e->getMessage(), [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Send interactive buttons (formatted as numbered options)
     * Note: WhatsApp interactive messages require templates for non-sandbox use
     *
     * @param string $to Phone number
     * @param string $message Message text
     * @param array $buttons Array of button labels
     * @return bool Success status
     */
    public function sendButtonMessage(string $to, string $message, array $buttons): bool
    {
        // Format buttons as numbered options since interactive buttons
        // require approved templates outside of sandbox
        $buttonText = "\n\n";
        foreach ($buttons as $index => $button) {
            $buttonText .= ($index + 1) . ". " . $button . "\n";
        }
        
        $fullMessage = $message . $buttonText . "\nReply with the number of your choice.";
        
        return $this->sendMessage($to, $fullMessage);
    }
    
    /**
     * Send a template message (for production WhatsApp Business)
     *
     * @param string $to Phone number
     * @param string $templateSid Template SID from Twilio
     * @param array $variables Template variables
     * @return bool Success status
     */
    public function sendTemplateMessage(string $to, string $templateSid, array $variables = []): bool
    {
        try {
            $client = $this->getClient();
            
            if (!$client) {
                Log::error('Twilio WhatsApp: Client not configured');
                return false;
            }
            
            $toNumber = self::formatWhatsAppNumber($to);
            
            $params = [
                'from' => $this->fromNumber,
                'contentSid' => $templateSid,
            ];
            
            if (!empty($variables)) {
                $params['contentVariables'] = json_encode($variables);
            }
            
            $response = $client->messages->create($toNumber, $params);
            
            Log::info("Twilio WhatsApp template sent", [
                'to' => $toNumber,
                'sid' => $response->sid,
                'template' => $templateSid
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Twilio WhatsApp template error: " . $e->getMessage(), [
                'to' => $to,
                'template' => $templateSid,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Format phone number for WhatsApp
     *
     * @param string $phoneNumber Raw phone number
     * @return string Formatted as whatsapp:+number
     */
    public static function formatWhatsAppNumber(string $phoneNumber): string
    {
        // If already in WhatsApp format, return as-is
        if (str_starts_with($phoneNumber, 'whatsapp:')) {
            return $phoneNumber;
        }
        
        // Remove spaces, dashes, parentheses
        $phoneNumber = preg_replace('/[\s\-\(\)]/', '', $phoneNumber);
        
        // Ensure it starts with +
        if (!str_starts_with($phoneNumber, '+')) {
            // Handle Zimbabwe numbers starting with 0
            if (str_starts_with($phoneNumber, '0')) {
                $phoneNumber = '+263' . substr($phoneNumber, 1);
            } else if (!str_starts_with($phoneNumber, '263')) {
                $phoneNumber = '+' . $phoneNumber;
            } else {
                $phoneNumber = '+' . $phoneNumber;
            }
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
        return !empty($this->accountSid) 
            && !empty($this->authToken) 
            && !empty($this->fromNumber);
    }
    
    /**
     * Get configuration status for debugging
     *
     * @return array
     */
    public function getConfigStatus(): array
    {
        return [
            'account_sid_set' => !empty($this->accountSid),
            'auth_token_set' => !empty($this->authToken),
            'from_number_set' => !empty($this->fromNumber),
            'from_number' => $this->fromNumber ? substr($this->fromNumber, 0, 15) . '...' : '[NOT SET]',
            'business_id_set' => !empty($this->whatsappBusinessId),
            'is_configured' => $this->isConfigured(),
        ];
    }
}
