<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppConversationService;
use App\Services\ReferenceCodeService;
use App\Services\StateManager;
use App\Services\TwilioWhatsAppService;

class WhatsAppWebhookController extends Controller
{
    private $conversationService;
    private $referenceCodeService;
    private $stateManager;
    private $twilioService;

    public function __construct(
        WhatsAppConversationService $conversationService,
        ReferenceCodeService $referenceCodeService,
        StateManager $stateManager,
        TwilioWhatsAppService $twilioService
    ) {
        $this->conversationService = $conversationService;
        $this->referenceCodeService = $referenceCodeService;
        $this->stateManager = $stateManager;
        $this->twilioService = $twilioService;
    }

    /**
     * Handle incoming WhatsApp webhooks from Twilio
     */
    public function handleWebhook(Request $request)
    {
        Log::info('WhatsApp webhook received', $request->all());

        // Validate Twilio signature for security
        if (!$this->validateTwilioSignature($request)) {
            Log::warning('Invalid Twilio signature');
            return response('Unauthorized', 401);
        }

        // Extract message details
        $from = $request->input('From');
        $body = $request->input('Body', '');
        $messageType = $request->input('MessageType', 'text');

        // Only process text messages for now
        if ($messageType !== 'text') {
            Log::info("Ignoring non-text message type: {$messageType}");
            return response('', 200);
        }

        try {
            // Check for specific reference code commands first
            if ($this->handleReferenceCodeCommands($from, $body)) {
                return response('', 200);
            }

            // Process the message through conversation service
            $this->conversationService->processIncomingMessage($from, $body);
            
            return response('', 200);
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp message: ' . $e->getMessage());
            return response('Internal Server Error', 500);
        }
    }

    /**
     * Validate Twilio webhook signature
     */
    private function validateTwilioSignature(Request $request): bool
    {
        $authToken = config('services.twilio.auth_token');
        $signature = $request->header('X-Twilio-Signature');

        if (!$signature || !$authToken) {
            return false;
        }

        // Build the full URL
        $url = $request->fullUrl();
        
        // Get POST parameters
        $postVars = $request->all();
        
        // Sort parameters
        ksort($postVars);
        
        // Create the signature string
        $data = $url;
        foreach ($postVars as $key => $value) {
            $data .= $key . $value;
        }
        
        // Generate expected signature
        $expectedSignature = base64_encode(hash_hmac('sha1', $data, $authToken, true));
        
        return hash_equals($signature, $expectedSignature);
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
            
            $this->twilioService->sendMessage($from, 
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
            
            $this->twilioService->sendMessage($from, 
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
        
        $this->twilioService->sendMessage($from, $message);
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
        
        $this->twilioService->sendMessage($from, $message);
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
        
        $this->twilioService->sendMessage($from, $message);
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
        
        // Extract status information
        $messageStatus = $request->input('MessageStatus');
        $messageSid = $request->input('MessageSid');
        $to = $request->input('To');
        
        // Log the status for monitoring
        Log::info("Message {$messageSid} to {$to} status: {$messageStatus}");
        
        return response('', 200);
    }
}