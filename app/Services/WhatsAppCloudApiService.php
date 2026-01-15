<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * WhatsApp Cloud API Service
 * 
 * Direct integration with Meta's WhatsApp Cloud API for sending and receiving messages.
 * Replaces TwilioWhatsAppService with native WhatsApp Business API functionality.
 */
class WhatsAppCloudApiService
{
    protected ?string $apiToken;
    protected ?string $phoneNumberId;
    protected ?string $businessId;
    protected ?string $verifyToken;
    protected string $apiVersion;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiToken = config('services.whatsapp_cloud.api_token');
        $this->phoneNumberId = config('services.whatsapp_cloud.phone_number_id');
        $this->businessId = config('services.whatsapp_cloud.business_id');
        $this->verifyToken = config('services.whatsapp_cloud.verify_token');
        $this->apiVersion = config('services.whatsapp_cloud.api_version', 'v18.0');
        $this->apiUrl = config('services.whatsapp_cloud.api_url', 'https://graph.facebook.com');
    }

    /**
     * Check if the service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiToken) && !empty($this->phoneNumberId);
    }

    /**
     * Get the verification token for webhook setup
     */
    public function getVerifyToken(): ?string
    {
        return $this->verifyToken;
    }

    /**
     * Send a text message
     */
    public function sendMessage(string $to, string $message): bool
    {
        try {
            if (!$this->isConfigured()) {
                Log::error('WhatsApp Cloud API: Service not configured');
                return false;
            }

            $phoneNumber = $this->formatPhoneNumber($to);

            $response = Http::withToken($this->apiToken)
                ->post($this->getMessagesEndpoint(), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $phoneNumber,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp Cloud API message sent', [
                    'to' => $phoneNumber,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                ]);
                return true;
            }

            Log::error('WhatsApp Cloud API send error', [
                'to' => $phoneNumber,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp Cloud API exception: ' . $e->getMessage(), [
                'to' => $to,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Send a message with media (image, document, etc.)
     */
    public function sendMessageWithMedia(string $to, string $mediaUrl, ?string $caption = null, string $type = 'image'): bool
    {
        try {
            if (!$this->isConfigured()) {
                Log::error('WhatsApp Cloud API: Service not configured');
                return false;
            }

            $phoneNumber = $this->formatPhoneNumber($to);
            $validTypes = ['image', 'document', 'audio', 'video', 'sticker'];
            
            if (!in_array($type, $validTypes)) {
                $type = 'image';
            }

            $mediaPayload = [
                'link' => $mediaUrl,
            ];

            // Add caption for supported types
            if ($caption && in_array($type, ['image', 'document', 'video'])) {
                $mediaPayload['caption'] = $caption;
            }

            // Documents need a filename
            if ($type === 'document') {
                $mediaPayload['filename'] = basename(parse_url($mediaUrl, PHP_URL_PATH)) ?: 'document';
            }

            $response = Http::withToken($this->apiToken)
                ->post($this->getMessagesEndpoint(), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $phoneNumber,
                    'type' => $type,
                    $type => $mediaPayload,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp Cloud API media message sent', [
                    'to' => $phoneNumber,
                    'type' => $type,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                ]);
                return true;
            }

            Log::error('WhatsApp Cloud API media send error', [
                'to' => $phoneNumber,
                'type' => $type,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp Cloud API media exception: ' . $e->getMessage(), [
                'to' => $to,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Send a template message (for initiating conversations or notifications)
     */
    public function sendTemplate(string $to, string $templateName, array $components = [], string $language = 'en'): bool
    {
        try {
            if (!$this->isConfigured()) {
                Log::error('WhatsApp Cloud API: Service not configured');
                return false;
            }

            $phoneNumber = $this->formatPhoneNumber($to);

            $templatePayload = [
                'name' => $templateName,
                'language' => [
                    'code' => $language,
                ],
            ];

            if (!empty($components)) {
                $templatePayload['components'] = $components;
            }

            $response = Http::withToken($this->apiToken)
                ->post($this->getMessagesEndpoint(), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $phoneNumber,
                    'type' => 'template',
                    'template' => $templatePayload,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp Cloud API template sent', [
                    'to' => $phoneNumber,
                    'template' => $templateName,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                ]);
                return true;
            }

            Log::error('WhatsApp Cloud API template send error', [
                'to' => $phoneNumber,
                'template' => $templateName,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp Cloud API template exception: ' . $e->getMessage(), [
                'to' => $to,
                'template' => $templateName,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Send interactive message with buttons
     */
    public function sendInteractiveButtons(string $to, string $bodyText, array $buttons, ?string $headerText = null, ?string $footerText = null): bool
    {
        try {
            if (!$this->isConfigured()) {
                Log::error('WhatsApp Cloud API: Service not configured');
                return false;
            }

            $phoneNumber = $this->formatPhoneNumber($to);

            $interactive = [
                'type' => 'button',
                'body' => [
                    'text' => $bodyText,
                ],
                'action' => [
                    'buttons' => array_map(function ($button, $index) {
                        return [
                            'type' => 'reply',
                            'reply' => [
                                'id' => $button['id'] ?? "btn_$index",
                                'title' => substr($button['title'], 0, 20), // Max 20 chars
                            ],
                        ];
                    }, array_slice($buttons, 0, 3), array_keys($buttons)), // Max 3 buttons
                ],
            ];

            if ($headerText) {
                $interactive['header'] = [
                    'type' => 'text',
                    'text' => $headerText,
                ];
            }

            if ($footerText) {
                $interactive['footer'] = [
                    'text' => $footerText,
                ];
            }

            $response = Http::withToken($this->apiToken)
                ->post($this->getMessagesEndpoint(), [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $phoneNumber,
                    'type' => 'interactive',
                    'interactive' => $interactive,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp Cloud API interactive message sent', [
                    'to' => $phoneNumber,
                    'message_id' => $data['messages'][0]['id'] ?? null,
                ]);
                return true;
            }

            Log::error('WhatsApp Cloud API interactive send error', [
                'to' => $phoneNumber,
                'status' => $response->status(),
                'error' => $response->json(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('WhatsApp Cloud API interactive exception: ' . $e->getMessage(), [
                'to' => $to,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Download media from WhatsApp Cloud API
     * 
     * When receiving media messages, the webhook only provides a media ID.
     * This method retrieves the actual media URL and downloads the content.
     */
    public function downloadMedia(string $mediaId): ?array
    {
        try {
            if (!$this->isConfigured()) {
                Log::error('WhatsApp Cloud API: Service not configured');
                return null;
            }

            // First, get the media URL
            $mediaInfoResponse = Http::withToken($this->apiToken)
                ->get("{$this->apiUrl}/{$this->apiVersion}/{$mediaId}");

            if (!$mediaInfoResponse->successful()) {
                Log::error('WhatsApp Cloud API: Failed to get media info', [
                    'media_id' => $mediaId,
                    'error' => $mediaInfoResponse->json(),
                ]);
                return null;
            }

            $mediaInfo = $mediaInfoResponse->json();
            $mediaUrl = $mediaInfo['url'] ?? null;
            $mimeType = $mediaInfo['mime_type'] ?? null;

            if (!$mediaUrl) {
                Log::error('WhatsApp Cloud API: No URL in media info', [
                    'media_id' => $mediaId,
                ]);
                return null;
            }

            // Now download the actual file
            $fileResponse = Http::withToken($this->apiToken)->get($mediaUrl);

            if (!$fileResponse->successful()) {
                Log::error('WhatsApp Cloud API: Failed to download media', [
                    'media_id' => $mediaId,
                    'url' => $mediaUrl,
                ]);
                return null;
            }

            return [
                'content' => $fileResponse->body(),
                'mime_type' => $mimeType,
                'sha256' => $mediaInfo['sha256'] ?? null,
                'file_size' => $mediaInfo['file_size'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp Cloud API download media exception: ' . $e->getMessage(), [
                'media_id' => $mediaId,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Mark a message as read
     */
    public function markAsRead(string $messageId): bool
    {
        try {
            if (!$this->isConfigured()) {
                return false;
            }

            $response = Http::withToken($this->apiToken)
                ->post($this->getMessagesEndpoint(), [
                    'messaging_product' => 'whatsapp',
                    'status' => 'read',
                    'message_id' => $messageId,
                ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('WhatsApp Cloud API mark as read error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format phone number to E.164 format (without + prefix)
     * 
     * WhatsApp Cloud API requires phone numbers in format: 263771234567
     * (country code + number, no + symbol, no spaces or dashes)
     */
    public function formatPhoneNumber(string $number): string
    {
        // Remove the 'whatsapp:' prefix if present (from Twilio format)
        $number = preg_replace('/^whatsapp:/i', '', $number);
        
        // Remove any non-digit characters
        $number = preg_replace('/\D/', '', $number);
        
        // If it starts with 0, assume Zimbabwe and add country code
        if (str_starts_with($number, '0')) {
            $number = '263' . substr($number, 1);
        }
        
        // If it starts with +, remove it
        $number = ltrim($number, '+');
        
        return $number;
    }

    /**
     * Extract phone number from WhatsApp format to local format
     * (Inverse of formatPhoneNumber for display purposes)
     */
    public static function extractPhoneNumber(string $number): string
    {
        // Remove whatsapp: prefix if present
        $number = preg_replace('/^whatsapp:/i', '', $number);
        
        // Remove + if present
        $number = ltrim($number, '+');
        
        // Remove non-digits
        $number = preg_replace('/\D/', '', $number);
        
        // If starts with 263, convert to local format
        if (str_starts_with($number, '263')) {
            $number = '0' . substr($number, 3);
        }
        
        return $number;
    }

    /**
     * Get the messages API endpoint
     */
    protected function getMessagesEndpoint(): string
    {
        return "{$this->apiUrl}/{$this->apiVersion}/{$this->phoneNumberId}/messages";
    }

    /**
     * Get phone number ID (for webhook validation)
     */
    public function getPhoneNumberId(): ?string
    {
        return $this->phoneNumberId;
    }

    /**
     * Get business ID
     */
    public function getBusinessId(): ?string
    {
        return $this->businessId;
    }
}
