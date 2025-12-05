<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppConversationService;
use App\Services\ReferenceCodeService;
use App\Services\StateManager;
use App\Services\RapiWhaService;

class WhatsAppWebhookController extends Controller
{
    private $conversationService;
    private $referenceCodeService;
    private $stateManager;
    private $rapiWhaService;

    public function __construct(
        WhatsAppConversationService $conversationService,
        ReferenceCodeService $referenceCodeService,
        StateManager $stateManager,
        RapiWhaService $rapiWhaService
    ) {
        $this->conversationService = $conversationService;
        $this->referenceCodeService = $referenceCodeService;
        $this->stateManager = $stateManager;
        $this->rapiWhaService = $rapiWhaService;
    }

    /**
     * Handle incoming WhatsApp webhooks from RapiWha
     */
    public function handleWebhook(Request $request)
    {
        Log::info('WhatsApp webhook received', $request->all());

        // RapiWha webhook payload typically contains:
        // - number: sender phone number
        // - message: message content
        // - type: message type (text, image, document, etc.)
        
        // Extract message details - support multiple payload formats
        $from = $request->input('number') 
             ?? $request->input('from') 
             ?? $request->input('From');
        
        $body = $request->input('message') 
             ?? $request->input('text') 
             ?? $request->input('body') 
             ?? $request->input('Body', '');
        
        $messageType = $request->input('type') 
                    ?? $request->input('MessageType', 'text');

        if (empty($from)) {
            Log::warning('WhatsApp webhook received without sender number');
            return response()->json(['status' => 'error', 'message' => 'Missing sender number'], 400);
        }

        // Format the from number for consistency
        $from = RapiWhaService::formatWhatsAppNumber($from);

        // Handle media messages (ID uploads)
        if (in_array($messageType, ['image', 'document', 'media'])) {
            return $this->handleMediaMessage($from, $request);
        }

        // Handle text messages
        if ($messageType === 'text' || $messageType === 'chat') {
            try {
                // Check for specific reference code commands first
                if ($this->handleReferenceCodeCommands($from, $body)) {
                    return response()->json(['status' => 'ok'], 200);
                }

                // Process the message through conversation service
                $this->conversationService->processIncomingMessage($from, $body);
                
                return response()->json(['status' => 'ok'], 200);
            } catch (\Exception $e) {
                Log::error('Error processing WhatsApp message: ' . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
            }
        }

        // Other message types - acknowledge but don't process
        Log::info("Ignoring message type: {$messageType}");
        return response()->json(['status' => 'ok'], 200);
    }
    
    /**
     * Handle media messages (ID document uploads)
     */
    private function handleMediaMessage(string $from, Request $request)
    {
        try {
            $phoneNumber = RapiWhaService::extractPhoneNumber($from);
            
            // Get current state
            $state = $this->stateManager->retrieveState('whatsapp_' . $phoneNumber, 'whatsapp');
            
            if (!$state) {
                Log::warning('Media message received but no active session', ['from' => $from]);
                $this->rapiWhaService->sendMessage($from, "Please start a conversation first by sending 'Hi' or 'Hello'.");
                return response()->json(['status' => 'ok'], 200);
            }
            
            // Check if we're expecting ID upload
            if (!in_array($state->current_step, ['agent_id_upload', 'agent_id_back_upload'])) {
                Log::info('Media received but not in ID upload state', [
                    'from' => $from,
                    'current_step' => $state->current_step
                ]);
                $this->rapiWhaService->sendMessage($from, "I wasn't expecting a photo right now. Please continue with the conversation.");
                return response()->json(['status' => 'ok'], 200);
            }
            
            // Extract media URL from various possible fields
            $mediaUrl = $request->input('url') 
                     ?? $request->input('media_url')
                     ?? $request->input('mediaUrl')
                     ?? $request->input('fileUrl');
            
            if (empty($mediaUrl)) {
                Log::warning('Media message received without URL', ['request' => $request->all()]);
                $this->rapiWhaService->sendMessage($from, "Sorry, I couldn't receive your photo. Please try sending it again.");
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
                
                $this->rapiWhaService->sendMessage($from, $msg);
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
        $phoneNumber = RapiWhaService::extractPhoneNumber($from);
        
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
            
           $this->rapiWhaService->sendMessage($from, $msg);
            
            Log::info('Agent application submitted via webhook', [
                'application_id' => $application->id,
                'whatsapp_number' => $phoneNumber
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to save agent application in webhook: ' . $e->getMessage());
            
            $msg = "❌ Sorry, there was an error saving your application. Please try again later or contact support.";
            $this->rapiWhaService->sendMessage($from, $msg);
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
        $phoneNumber = RapiWhaService::extractPhoneNumber($from);
        
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
            
            $this->rapiWhaService->sendMessage($from, 
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
        $phoneNumber = RapiWhaService::extractPhoneNumber($from);
        
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
            
            $this->rapiWhaService->sendMessage($from, 
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
        
        $this->rapiWhaService->sendMessage($from, $message);
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
        
        $this->rapiWhaService->sendMessage($from, $message);
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
        
        $this->rapiWhaService->sendMessage($from, $message);
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
        
        // Extract status information from RapiWha payload
        $messageId = $request->input('message_id') ?? $request->input('MessageId');
        $status = $request->input('status') ?? $request->input('MessageStatus');
        $to = $request->input('number') ?? $request->input('To');
        
        // Log the status for monitoring
        Log::info("Message {$messageId} to {$to} status: {$status}");
        
        return response()->json(['status' => 'ok'], 200);
    }
}