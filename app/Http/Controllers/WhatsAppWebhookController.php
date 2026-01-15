<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\WhatsAppConversationService;
use App\Services\ReferenceCodeService;
use App\Services\StateManager;
use App\Services\TwilioWhatsAppService;
use App\Services\WhatsAppCloudApiService;
// use App\Services\RapiWhaService; // DEPRECATED: Switched to Twilio 2025-12-06

class WhatsAppWebhookController extends Controller
{
    private $conversationService;
    private $referenceCodeService;
    private $stateManager;
    private TwilioWhatsAppService $whatsAppService;
    private WhatsAppCloudApiService $cloudApiService;

    public function __construct(
        WhatsAppConversationService $conversationService,
        ReferenceCodeService $referenceCodeService,
        StateManager $stateManager,
        TwilioWhatsAppService $whatsAppService,
        WhatsAppCloudApiService $cloudApiService
    ) {
        $this->conversationService = $conversationService;
        $this->referenceCodeService = $referenceCodeService;
        $this->stateManager = $stateManager;
        $this->whatsAppService = $whatsAppService;
        $this->cloudApiService = $cloudApiService;
    }

    /**
     * Verify webhook for WhatsApp Cloud API setup
     * 
     * Meta requires this endpoint to respond to a GET request with hub.challenge
     * when setting up webhooks in the Facebook Developer Dashboard.
     */
    public function verifyWebhook(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        Log::info('WhatsApp Cloud API webhook verification attempt', [
            'mode' => $mode,
            'token_provided' => !empty($token),
            'challenge' => $challenge
        ]);

        // Check if mode and token are correct
        if ($mode === 'subscribe' && $token === $this->cloudApiService->getVerifyToken()) {
            Log::info('WhatsApp Cloud API webhook verified successfully');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp Cloud API webhook verification failed', [
            'expected_token' => $this->cloudApiService->getVerifyToken(),
            'received_token' => $token
        ]);
        return response('Forbidden', 403);
    }

    /**
     * Handle incoming WhatsApp webhooks from Twilio
     * 
     * Twilio sends webhooks with these fields:
     * - From: whatsapp:+1234567890
     * - Body: message text
     * - NumMedia: number of media attachments
     * - MediaUrl0, MediaUrl1, etc: media URLs
     * - MessageSid: unique message ID
     */
    public function handleWebhook(Request $request)
    {
        Log::info('WhatsApp webhook received', $request->all());

        // Detect if this is a WhatsApp Cloud API webhook (Meta format)
        if ($request->input('object') === 'whatsapp_business_account') {
            return $this->handleCloudApiWebhook($request);
        }

        // Otherwise, handle as Twilio webhook (legacy format)
        return $this->handleTwilioWebhook($request);
    }

    /**
     * Handle WhatsApp Cloud API webhooks from Meta
     */
    protected function handleCloudApiWebhook(Request $request)
    {
        try {
            $entries = $request->input('entry', []);

            foreach ($entries as $entry) {
                $changes = $entry['changes'] ?? [];

                foreach ($changes as $change) {
                    $value = $change['value'] ?? [];
                    
                    // Handle incoming messages
                    if (isset($value['messages'])) {
                        foreach ($value['messages'] as $message) {
                            $this->processCloudApiMessage($message, $value);
                        }
                    }

                    // Handle status updates
                    if (isset($value['statuses'])) {
                        foreach ($value['statuses'] as $status) {
                            $this->processCloudApiStatus($status);
                        }
                    }
                }
            }

            // Always return 200 to acknowledge receipt
            return response()->json(['status' => 'ok'], 200);

        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp Cloud API webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Still return 200 to prevent Meta from retrying
            return response()->json(['status' => 'ok'], 200);
        }
    }

    /**
     * Process a single message from WhatsApp Cloud API
     */
    protected function processCloudApiMessage(array $message, array $value): void
    {
        $messageId = $message['id'] ?? null;
        $from = $message['from'] ?? null;
        $timestamp = $message['timestamp'] ?? null;
        $type = $message['type'] ?? 'text';

        if (!$from) {
            Log::warning('Cloud API message missing from field');
            return;
        }

        // Idempotency check
        if ($messageId) {
            $cacheKey = 'whatsapp_msg_' . $messageId;
            if (Cache::has($cacheKey)) {
                Log::info('WhatsApp Cloud API: Duplicate message ignored', ['id' => $messageId]);
                return;
            }
            Cache::put($cacheKey, true, 86400); // 24 hours
        }

        // Format phone number to consistent format
        $formattedFrom = 'whatsapp:+' . $from;

        Log::info('WhatsApp Cloud API message received', [
            'from' => $from,
            'type' => $type,
            'message_id' => $messageId
        ]);

        // Mark message as read
        if ($messageId) {
            $this->cloudApiService->markAsRead($messageId);
        }

        // Handle different message types
        switch ($type) {
            case 'text':
                $text = $message['text']['body'] ?? '';
                $this->processTextMessage($formattedFrom, $text);
                break;

            case 'image':
            case 'document':
            case 'video':
            case 'audio':
                $this->processCloudApiMediaMessage($formattedFrom, $message, $type);
                break;

            case 'interactive':
                // Handle button replies
                $interactive = $message['interactive'] ?? [];
                $interactiveType = $interactive['type'] ?? '';
                
                if ($interactiveType === 'button_reply') {
                    $buttonId = $interactive['button_reply']['id'] ?? '';
                    $buttonTitle = $interactive['button_reply']['title'] ?? '';
                    $this->processTextMessage($formattedFrom, $buttonTitle ?: $buttonId);
                } elseif ($interactiveType === 'list_reply') {
                    $listId = $interactive['list_reply']['id'] ?? '';
                    $listTitle = $interactive['list_reply']['title'] ?? '';
                    $this->processTextMessage($formattedFrom, $listTitle ?: $listId);
                }
                break;

            case 'button':
                // Template button replies
                $buttonText = $message['button']['text'] ?? '';
                $this->processTextMessage($formattedFrom, $buttonText);
                break;

            default:
                Log::info('Unhandled Cloud API message type', [
                    'type' => $type,
                    'from' => $from
                ]);
        }
    }

    /**
     * Process media message from Cloud API
     */
    protected function processCloudApiMediaMessage(string $from, array $message, string $type): void
    {
        $mediaId = $message[$type]['id'] ?? null;
        $caption = $message[$type]['caption'] ?? '';
        $mimeType = $message[$type]['mime_type'] ?? '';

        if (!$mediaId) {
            Log::warning('Cloud API media message missing media ID');
            return;
        }

        Log::info('Processing Cloud API media message', [
            'from' => $from,
            'type' => $type,
            'media_id' => $mediaId
        ]);

        // Download the media
        $mediaData = $this->cloudApiService->downloadMedia($mediaId);

        if (!$mediaData) {
            Log::error('Failed to download Cloud API media', ['media_id' => $mediaId]);
            $this->cloudApiService->sendMessage($from, "Sorry, we couldn't process your media. Please try again.");
            return;
        }

        // Create a simulated request for the existing media handler
        $request = new Request();
        $request->merge([
            'cloud_api_media' => true,
            'media_content' => base64_encode($mediaData['content']),
            'media_mime_type' => $mediaData['mime_type'] ?? $mimeType,
            'media_type' => $type,
            'caption' => $caption
        ]);

        $this->handleMediaMessage($from, $request);
    }

    /**
     * Process status update from Cloud API
     */
    protected function processCloudApiStatus(array $status): void
    {
        $messageId = $status['id'] ?? null;
        $statusType = $status['status'] ?? null;
        $recipientId = $status['recipient_id'] ?? null;
        $timestamp = $status['timestamp'] ?? null;

        Log::info('WhatsApp Cloud API status update', [
            'message_id' => $messageId,
            'status' => $statusType,
            'recipient' => $recipientId
        ]);

        // Handle delivery errors
        if (isset($status['errors'])) {
            foreach ($status['errors'] as $error) {
                Log::error('WhatsApp Cloud API delivery error', [
                    'message_id' => $messageId,
                    'error_code' => $error['code'] ?? null,
                    'error_title' => $error['title'] ?? null,
                    'error_message' => $error['message'] ?? null
                ]);
            }
        }
    }

    /**
     * Process text message (shared between Cloud API and Twilio)
     */
    protected function processTextMessage(string $from, string $body): void
    {
        try {
            // DEBUG: Test direct message sending
            if (strtolower(trim($body)) === 'test') {
                Log::info('WhatsApp TEST: Attempting direct message send');
                $result = $this->cloudApiService->sendMessage($from, 'Test message received! Bot is working with Cloud API.');
                Log::info('WhatsApp TEST: Send result', ['success' => $result]);
                return;
            }

            // Check for reference code commands
            if ($this->handleReferenceCodeCommands($from, $body)) {
                return;
            }

            // Process through conversation service
            $this->conversationService->processIncomingMessage($from, $body);

        } catch (\Exception $e) {
            Log::error('Error processing text message', [
                'error' => $e->getMessage(),
                'from' => $from,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle Twilio webhook format (legacy)
     */
    protected function handleTwilioWebhook(Request $request)
    {
        // Twilio webhook payload
        // - From: sender phone number (whatsapp:+1234567890)
        // - Body: message content
        // - NumMedia: number of media attachments
        // - MediaUrl0, MediaContentType0, etc: media info
        
        // Extract message details - support Twilio format (primary) and fallback formats
        $from = $request->input('From') 
             ?? $request->input('from')
             ?? $request->input('number');
        
        $body = $request->input('Body') 
             ?? $request->input('body')
             ?? $request->input('message')
             ?? $request->input('text', '');
        
        $numMedia = (int) $request->input('NumMedia', 0);
        $messageSid = $request->input('MessageSid');

        if (empty($from)) {
            Log::warning('WhatsApp webhook received without sender number');
            return response()->json(['status' => 'error', 'message' => 'Missing sender number'], 400);
        }

        // Idempotency Check: Prevent duplicate processing of the same message (Twilio retries)
        if ($messageSid) {
            $cacheKey = 'whatsapp_msg_' . $messageSid;
            if (Cache::has($cacheKey)) {
                Log::info("WhatsApp: Duplicate message ignored", ['sid' => $messageSid]);
                // Return 200 OK immediately to stop Twilio from retrying further
                return response()->json(['status' => 'ok', 'duplicate' => true], 200);
            }
            // Store MessageSid for 24 hours
            Cache::put($cacheKey, true, 86400);
        }

        // Format the from number for consistency
        $from = TwilioWhatsAppService::formatWhatsAppNumber($from);
        
        Log::info('WhatsApp processing message', [
            'from' => $from,
            'body' => $body,
            'numMedia' => $numMedia
        ]);

        // Handle media messages (ID uploads) - Twilio sends NumMedia > 0
        if ($numMedia > 0) {
            return $this->handleMediaMessage($from, $request);
        }

        // Handle text messages
        try {
            // DEBUG: Test direct message sending first
            if (strtolower(trim($body)) === 'test') {
                Log::info('WhatsApp TEST: Attempting direct message send');
                $result = $this->whatsAppService->sendMessage($from, 'Test message received! Bot is working.');
                Log::info('WhatsApp TEST: Send result', ['success' => $result]);
                return response()->json(['status' => 'ok', 'test' => 'sent'], 200);
            }
            
            // Check for specific reference code commands first
            if ($this->handleReferenceCodeCommands($from, $body)) {
                return response()->json(['status' => 'ok'], 200);
            }

            Log::info('WhatsApp: Calling processIncomingMessage');
            
            // Process the message through conversation service
            $this->conversationService->processIncomingMessage($from, $body);
            
            Log::info('WhatsApp: processIncomingMessage completed');
            
            return response()->json(['status' => 'ok'], 200);
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp message', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }
    
    /**
     * Handle media messages (ID document uploads)
     */
    private function handleMediaMessage(string $from, Request $request)
    {
        try {
            $phoneNumber = TwilioWhatsAppService::extractPhoneNumber($from);
            
            // Get current state - Corrected to use phoneNumber instead of session ID string
            $state = $this->stateManager->retrieveState($phoneNumber, 'whatsapp');
            
            if (!$state) {
                Log::warning('Media message received but no active session found', [
                    'from' => $from,
                    'phone_lookup' => $phoneNumber
                ]);
                $this->whatsAppService->sendMessage($from, "Please start a conversation first by sending 'Hi' or 'Hello'.");
                return response()->json(['status' => 'ok'], 200);
            }
            
            // Check if we're expecting ID upload
            if (!in_array($state->current_step, ['agent_id_upload', 'agent_id_back_upload'])) {
                Log::info('Media received but not in ID upload state', [
                    'from' => $from,
                    'current_step' => $state->current_step
                ]);
                $this->whatsAppService->sendMessage($from, "I wasn't expecting a photo right now. Please continue with the conversation.");
                return response()->json(['status' => 'ok'], 200);
            }
            
            // Extract media URL - Twilio uses MediaUrl0, MediaUrl1, etc.
            $mediaUrl = $request->input('MediaUrl0') 
                     ?? $request->input('url') 
                     ?? $request->input('media_url')
                     ?? $request->input('mediaUrl');
            
            if (empty($mediaUrl)) {
                Log::warning('Media message received without URL', ['request' => $request->all()]);
                $this->whatsAppService->sendMessage($from, "Sorry, I couldn't receive your photo. Please try sending it again.");
                return response()->json(['status' => 'error', 'message' => 'No media URL'], 400);
            }
            
            // Determine which side of ID (front or back)
            $side = ($state->current_step === 'agent_id_upload') ? 'front' : 'back';
            
            // Process the ID upload through conversation service
            $formData = $state->form_data ?? [];
            
            if ($side === 'front') {
                $formData['id_front_url'] = $mediaUrl;
                
                $this->stateManager->saveState(
                    $state->session_id,
                    'whatsapp',
                    $state->user_identifier,
                    'agent_id_back_upload',
                    $formData,
                    $state->metadata ?? []
                );
                
                $msg = "✅ Front of ID received!\n\n";
                $msg .= "Now please send a clear photo of the *back* of your ID card.";
                
                $this->whatsAppService->sendMessage($from, $msg);
            } else {
                // Back of ID received - complete application
                $formData['id_back_url'] = $mediaUrl;
                
               // Save agent application to database
                $this->saveAgentApplication($from, $state, $formData);
            }
            
            return response()->json(['status' => 'ok'], 200);
            
        } catch (\Exception $e) {
            Log::error('Error handling media message: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }
    
    /**
     * Save agent application to database
     */
    private function saveAgentApplication(string $from, $state, array $formData): void
    {
        $phoneNumber = TwilioWhatsAppService::extractPhoneNumber($from);
        
        try {
            $application = \App\Models\AgentApplication::create([
                'whatsapp_number' => $phoneNumber,
                'session_id' => $state->session_id,
                'province' => $formData['province'] ?? '',
                'first_name' => $formData['first_name'] ?? '',
                'surname' => $formData['surname'] ?? '',
                'gender' => $formData['gender'] ?? '',
                'age_range' => $formData['age_range'] ?? '',
                'voice_number' => $formData['voice_number'] ?? '',
                'whatsapp_contact' => $formData['whatsapp_contact'] ?? '',
                'ecocash_number' => $formData['ecocash_number'] ?? '',
                'id_front_url' => $formData['id_front_url'] ?? null,
                'id_back_url' => $formData['id_back_url'] ?? null,
                'status' => 'pending',
            ]);
            
            $this->stateManager->saveState(
                $state->session_id,
                'whatsapp',
                $state->user_identifier,
                'completed',
                array_merge($formData, [
                    'outcome' => 'agent_application_submitted',
                    'application_id' => $application->id
                ]),
                $state->metadata ?? []
            );
            
            $msg = "🎉 *Application Submitted Successfully!*\n\n";
            $msg .= "Thank you, *{$formData['first_name']} {$formData['surname']}*!\n\n";
            $msg .= "Your agent application has been received. We will review your application and get back to you soon with your agent login details and referral link.\n\n";
            $msg .= "📧 You will be contacted via:\n";
            $msg .= "• SMS: {$formData['voice_number']}\n";
            $msg .= "• WhatsApp: {$formData['whatsapp_contact']}\n\n";
            $msg .= "Thank you for joining Microbiz Zimbabwe! 🚀";
            
           $this->whatsAppService->sendMessage($from, $msg);
            
            Log::info('Agent application submitted via webhook', [
                'application_id' => $application->id,
                'whatsapp_number' => $phoneNumber
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to save agent application in webhook: ' . $e->getMessage());
            
            $msg = "❌ Sorry, there was an error saving your application. Please try again later or contact support.";
            $this->whatsAppService->sendMessage($from, $msg);
        }
    }

    /**
     * Handle reference code specific commands
     * 
     * @param string $from WhatsApp number
     * @param string $message Message body
     * @return bool True if command was handled, false otherwise
     */
    private function handleReferenceCodeCommands(string $from, string $message): bool
    {
        $message = trim(strtolower($message));
        
        // Check for resume command with reference code
        if (preg_match('/^resume\s+([a-z0-9]{6})$/i', $message, $matches)) {
            $this->resumeApplication($from, strtoupper($matches[1]));
            return true;
        }
        
        // Check for status command with reference code
        if (preg_match('/^status\s+([a-z0-9]{6})$/i', $message, $matches)) {
            $this->checkApplicationStatus($from, strtoupper($matches[1]));
            return true;
        }
        
        // Check for standalone 6-character reference code
        if (preg_match('/^[a-z0-9]{6}$/i', $message)) {
            $referenceCode = strtoupper($message);
            
            // First try to resume, if that fails, try status check
            if ($this->referenceCodeService->validateReferenceCode($referenceCode)) {
                $this->resumeApplication($from, $referenceCode);
                return true;
            } else {
                $this->sendInvalidReferenceCode($from, $referenceCode);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Resume application using reference code
     * 
     * @param string $from WhatsApp number
     * @param string $referenceCode Reference code to resume
     */
    public function resumeApplication(string $from, string $referenceCode): void
    {
        $phoneNumber = TwilioWhatsAppService::extractPhoneNumber($from);
        
        try {
            // Get application state by reference code
            $applicationState = $this->referenceCodeService->getStateByReferenceCode($referenceCode);
            
            if (!$applicationState) {
                $this->sendInvalidReferenceCode($from, $referenceCode);
                return;
            }
            
            // Check if application is already completed
            if ($applicationState->current_step === 'completed') {
                $this->sendCompletedApplicationMessage($from, $applicationState, $referenceCode);
                return;
            }
            
            // Use conversation service to resume the application
            $this->conversationService->resumeApplication($from, $referenceCode);
            
            Log::info('Application resumed via WhatsApp webhook', [
                'phone_number' => $phoneNumber,
                'reference_code' => $referenceCode,
                'session_id' => $applicationState->session_id,
                'current_step' => $applicationState->current_step
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to resume application via WhatsApp webhook: ' . $e->getMessage(), [
                'phone_number' => $phoneNumber,
                'reference_code' => $referenceCode
            ]);
            
            $this->whatsAppService->sendMessage($from, 
                "❌ Sorry, there was an issue resuming your application. Please try again later or contact support."
            );
        }
    }

    /**
     * Check application status using reference code
     * 
     * @param string $from WhatsApp number
     * @param string $referenceCode Reference code to check
     */
    public function checkApplicationStatus(string $from, string $referenceCode): void
    {
        $phoneNumber = TwilioWhatsAppService::extractPhoneNumber($from);
        
        try {
            // Get application status by reference code
            $statusInfo = $this->referenceCodeService->getApplicationStatusByReferenceCode($referenceCode);
            
            if (!$statusInfo) {
                $this->sendInvalidReferenceCode($from, $referenceCode);
                return;
            }
            
            // Get application state for more details
            $applicationState = $this->referenceCodeService->getStateByReferenceCode($referenceCode);
            
            if (!$applicationState) {
                $this->sendInvalidReferenceCode($from, $referenceCode);
                return;
            }
            
            $this->sendApplicationStatusMessage($from, $applicationState, $referenceCode, $statusInfo);
            
            Log::info('Application status checked via WhatsApp', [
                'phone_number' => $phoneNumber,
                'reference_code' => $referenceCode,
                'status' => $statusInfo['status'],
                'current_step' => $statusInfo['current_step']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to check application status via WhatsApp: ' . $e->getMessage(), [
                'phone_number' => $phoneNumber,
                'reference_code' => $referenceCode
            ]);
            
            $this->whatsAppService->sendMessage($from, 
                "❌ Sorry, there was an issue checking your application status. Please try again later or contact support."
            );
        }
    }

    /**
     * Send invalid reference code message
     * 
     * @param string $from WhatsApp number
     * @param string $referenceCode Invalid reference code
     */
    private function sendInvalidReferenceCode(string $from, string $referenceCode): void
    {
        $message = "❌ *Invalid Reference Code*\n\n";
        $message .= "The reference code '{$referenceCode}' is not valid or has expired.\n\n";
        $message .= "*Valid commands:*\n";
        $message .= "• Type *'resume XXXXXX'* to continue an application\n";
        $message .= "• Type *'status XXXXXX'* to check application status\n";
        $message .= "• Type *'start'* to begin a new application\n\n";
        $message .= "Please check your reference code and try again.";
        
        $this->whatsAppService->sendMessage($from, $message);
    }

    /**
     * Send application status message
     * 
     * @param string $from WhatsApp number
     * @param ApplicationState $applicationState Application state
     * @param string $referenceCode Reference code
     * @param array $statusInfo Status information
     */
    private function sendApplicationStatusMessage(string $from, $applicationState, string $referenceCode, array $statusInfo): void
    {
        $formData = $applicationState->form_data ?? [];
        $metadata = $applicationState->metadata ?? [];
        
        // Get business and amount info if available
        $business = $formData['selectedBusiness']['name'] ?? 'N/A';
        $amount = isset($formData['finalPrice']) ? '$' . number_format($formData['finalPrice']) : 'N/A';
        
        // Determine status display
        $statusDisplay = $this->getStatusDisplay($statusInfo['status'], $statusInfo['current_step']);
        
        $message = "📊 *Application Status*\n\n";
        $message .= "🔐 Reference Code: *{$referenceCode}*\n";
        $message .= "📋 Status: {$statusDisplay}\n";
        $message .= "🏢 Business: {$business}\n";
        $message .= "💰 Amount: {$amount}\n";
        $message .= "📅 Last Updated: " . $applicationState->updated_at->format('Y-m-d H:i') . "\n\n";
        
        // Add step-specific information
        switch ($statusInfo['current_step']) {
            case 'completed':
                $message .= "✅ *Application Completed*\n";
                $message .= "Your application has been submitted and is being reviewed by our team.\n\n";
                $message .= "📄 Download your application:\n";
                $message .= config('app.url') . "/application/download/" . $applicationState->session_id . "\n\n";
                break;
                
            case 'form':
                $completedFields = count($formData['formResponses'] ?? []);
                $totalFields = count($formData['formFields'] ?? []);
                $message .= "📝 *Form in Progress*\n";
                $message .= "Progress: {$completedFields}/{$totalFields} fields completed\n";
                $message .= "Type 'resume {$referenceCode}' to continue.\n\n";
                break;
                
            case 'product':
            case 'business':
            case 'scale':
                $message .= "🛍️ *Product Selection*\n";
                $message .= "You're in the middle of selecting your loan product.\n";
                $message .= "Type 'resume {$referenceCode}' to continue.\n\n";
                break;
                
            default:
                $message .= "🔄 *Application in Progress*\n";
                $message .= "Type 'resume {$referenceCode}' to continue where you left off.\n\n";
        }
        
        $message .= "*Commands:*\n";
        $message .= "• Type *'resume {$referenceCode}'* to continue\n";
        $message .= "• Type *'start'* for a new application";
        
        $this->whatsAppService->sendMessage($from, $message);
    }

    /**
     * Send completed application message
     * 
     * @param string $from WhatsApp number
     * @param ApplicationState $applicationState Application state
     * @param string $referenceCode Reference code
     */
    private function sendCompletedApplicationMessage(string $from, $applicationState, string $referenceCode): void
    {
        $formData = $applicationState->form_data ?? [];
        $business = $formData['selectedBusiness']['name'] ?? 'N/A';
        $amount = isset($formData['finalPrice']) ? '$' . number_format($formData['finalPrice']) : 'N/A';
        
        $message = "✅ *Application Already Completed*\n\n";
        $message .= "🔐 Reference Code: *{$referenceCode}*\n";
        $message .= "🏢 Business: {$business}\n";
        $message .= "💰 Amount: {$amount}\n";
        $message .= "📅 Completed: " . $applicationState->updated_at->format('Y-m-d H:i') . "\n\n";
        $message .= "Your application has been submitted and is being reviewed.\n\n";
        $message .= "📄 Download your application:\n";
        $message .= config('app.url') . "/application/download/" . $applicationState->session_id . "\n\n";
        $message .= "📱 Check detailed status:\n";
        $message .= config('app.url') . "/application/status\n\n";
        $message .= "Type *'start'* to begin a new application.";
        
        $this->whatsAppService->sendMessage($from, $message);
    }

    /**
     * Get status display text
     * 
     * @param string $status Status code
     * @param string $currentStep Current step
     * @return string Display text
     */
    private function getStatusDisplay(string $status, string $currentStep): string
    {
        switch ($status) {
            case 'approved':
                return '✅ Approved';
            case 'rejected':
                return '❌ Rejected';
            case 'under_review':
                return '🔍 Under Review';
            case 'pending_documents':
                return '📄 Pending Documents';
            case 'completed':
                return '✅ Completed';
            case 'pending':
            default:
                if ($currentStep === 'completed') {
                    return '📋 Submitted';
                } else {
                    return '🔄 In Progress';
                }
        }
    }

    /**
     * Handle status updates (delivery receipts)
     */
    public function handleStatusUpdate(Request $request)
    {
        Log::info('WhatsApp status update received', $request->all());
        
        // Extract status information from Twilio payload
        $messageSid = $request->input('MessageSid');
        $status = $request->input('MessageStatus') ?? $request->input('SmsStatus'); // Twilio sends MessageStatus or SmsStatus
        $to = $request->input('To');
        
        // Log the status for monitoring
        Log::info("Twilio Message {$messageSid} to {$to} status: {$status}");
        
        return response()->json(['status' => 'ok'], 200);
    }
}