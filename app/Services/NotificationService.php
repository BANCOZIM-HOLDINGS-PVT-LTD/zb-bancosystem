<?php

namespace App\Services;

use App\Models\ApplicationState;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send a status update notification
     *
     * @param ApplicationState $applicationState
     * @param string $oldStatus
     * @param string $newStatus
     * @return bool
     */
    public function sendStatusUpdateNotification(ApplicationState $applicationState, string $oldStatus, string $newStatus): bool
    {
        try {
            $formData = $applicationState->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];

            // Get applicant name
            $applicantName = trim(
                ($formResponses['firstName'] ?? '') . ' ' .
                ($formResponses['lastName'] ?? ($formResponses['surname'] ?? ''))
            ) ?: 'Applicant';

            // Get applicant email
            $email = $formResponses['email'] ?? null;

            // Get applicant phone
            $phone = $formResponses['phone'] ?? $formResponses['phoneNumber'] ?? null;

            // Log the notification
            Log::info("Status update notification for {$applicationState->session_id}: {$oldStatus} -> {$newStatus}");

            // Send different notifications based on status change
            $this->sendStatusSpecificNotification($applicationState, $newStatus, $applicantName, $email, $phone);

            // Store notification in application metadata for display in UI
            $this->storeNotificationInMetadata($applicationState, $oldStatus, $newStatus);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send status update notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send status-specific notifications
     */
    private function sendStatusSpecificNotification(ApplicationState $applicationState, string $status, string $applicantName, ?string $email, ?string $phone): void
    {
        $nationalId = $applicationState->national_id ?? $applicationState->session_id;

        switch ($status) {
            case 'approved':
                $message = "Great news {$applicantName}! Your loan application ({$nationalId}) has been approved. Check your status at: " . url("/application/status?ref={$nationalId}");
                Log::info("APPROVAL notification: Would send to {$applicantName} ({$email}, {$phone}): {$message}");
                break;

            case 'rejected':
                $message = "Dear {$applicantName}, your loan application ({$nationalId}) requires additional review. Please check your status for details: " . url("/application/status?ref={$nationalId}");
                Log::info("REJECTION notification: Would send to {$applicantName} ({$email}, {$phone}): {$message}");
                break;

            case 'under_review':
                $message = "Hello {$applicantName}, your loan application ({$nationalId}) is now under review. We'll notify you of any updates.";
                Log::info("REVIEW notification: Would send to {$applicantName} ({$email}, {$phone}): {$message}");
                break;

            case 'completed':
                $message = "Congratulations {$applicantName}! Your loan has been processed and disbursed. Track delivery at: " . url("/delivery/tracking?ref={$nationalId}");
                Log::info("COMPLETION notification: Would send to {$applicantName} ({$email}, {$phone}): {$message}");
                break;
        }
    }

    /**
     * Store notification in application metadata for UI display
     */
    private function storeNotificationInMetadata(ApplicationState $applicationState, string $oldStatus, string $newStatus): void
    {
        $metadata = $applicationState->metadata ?? [];
        $metadata['notifications'] = $metadata['notifications'] ?? [];

        // Add new notification
        $metadata['notifications'][] = [
            'id' => uniqid('notif_'),
            'type' => $this->getNotificationType($newStatus),
            'title' => $this->getNotificationTitle($newStatus),
            'message' => $this->getNotificationMessage($newStatus, $oldStatus),
            'timestamp' => now()->toIso8601String(),
            'read' => false,
            'priority' => $this->getNotificationPriority($newStatus),
            'status_change' => [
                'from' => $oldStatus,
                'to' => $newStatus
            ],
            'actions' => $this->getNotificationActions($newStatus, $applicationState)
        ];

        // Keep only last 15 notifications (increased from 10)
        $metadata['notifications'] = array_slice($metadata['notifications'], -15);

        $applicationState->metadata = $metadata;
        $applicationState->save();
    }

    /**
     * Get notification priority based on status
     */
    private function getNotificationPriority(string $status): string
    {
        switch ($status) {
            case 'approved':
            case 'rejected':
            case 'completed':
                return 'high';
            case 'under_review':
                return 'medium';
            default:
                return 'low';
        }
    }

    /**
     * Get notification actions based on status
     */
    private function getNotificationActions(string $status, ApplicationState $applicationState): array
    {
        $actions = [];
        $referenceCode = $applicationState->reference_code ?? $applicationState->session_id;

        switch ($status) {
            case 'approved':
                $actions[] = [
                    'type' => 'link',
                    'label' => 'View Details',
                    'url' => "/application/status?ref={$referenceCode}"
                ];
                break;

            case 'rejected':
                $actions[] = [
                    'type' => 'link',
                    'label' => 'View Feedback',
                    'url' => "/application/status?ref={$referenceCode}"
                ];
                $actions[] = [
                    'type' => 'link',
                    'label' => 'Start New Application',
                    'url' => '/application'
                ];
                break;

            case 'completed':
                $actions[] = [
                    'type' => 'link',
                    'label' => 'Track Delivery',
                    'url' => "/delivery/tracking?ref={$referenceCode}"
                ];
                break;
        }

        return $actions;
    }

    /**
     * Get notification type based on status
     */
    private function getNotificationType(string $status): string
    {
        switch ($status) {
            case 'approved':
            case 'completed':
                return 'success';
            case 'rejected':
                return 'error';
            case 'under_review':
                return 'info';
            default:
                return 'info';
        }
    }

    /**
     * Get notification title based on status
     */
    private function getNotificationTitle(string $status): string
    {
        switch ($status) {
            case 'approved':
                return 'Application Approved!';
            case 'rejected':
                return 'Application Update';
            case 'under_review':
                return 'Application Under Review';
            case 'completed':
                return 'Loan Disbursed!';
            default:
                return 'Status Update';
        }
    }

    /**
     * Get notification message based on status
     */
    private function getNotificationMessage(string $newStatus, string $oldStatus): string
    {
        switch ($newStatus) {
            case 'approved':
                return 'Your loan application has been approved! Disbursement will be processed soon.';
            case 'rejected':
                return 'Your application requires additional review. Please check the details or contact support.';
            case 'under_review':
                return 'Our team is reviewing your application. We may contact you if additional information is needed.';
            case 'completed':
                return 'Your loan has been successfully disbursed. You can now track product delivery.';
            default:
                return "Your application status has been updated from {$oldStatus} to {$newStatus}.";
        }
    }

    /**
     * Send a reference code notification
     *
     * @param ApplicationState $applicationState
     * @param string $referenceCode
     * @return bool
     */
    public function sendReferenceCodeNotification(ApplicationState $applicationState, string $referenceCode): bool
    {
        try {
            $formData = $applicationState->form_data ?? [];
            $formResponses = $formData['formResponses'] ?? [];

            // Get applicant name
            $applicantName = trim(
                ($formResponses['firstName'] ?? '') . ' ' .
                ($formResponses['lastName'] ?? ($formResponses['surname'] ?? ''))
            ) ?: 'Applicant';

            // Get applicant email
            $email = $formResponses['email'] ?? null;

            // Get applicant phone
            $phone = $formResponses['phone'] ?? $formResponses['phoneNumber'] ?? null;

            // Log the notification
            Log::info("Reference code notification for {$applicationState->session_id}: {$referenceCode}");

            // In a real implementation, we would send an email or SMS here
            // For now, we'll just log it
            Log::info("Would send reference code {$referenceCode} to {$applicantName} ({$email}, {$phone})");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send reference code notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send real-time notification (enhanced with broadcasting support)
     */
    public function sendRealTimeNotification(ApplicationState $applicationState, array $notification): bool
    {
        try {
            // Log the notification
            Log::info("Real-time notification for {$applicationState->session_id}: " . json_encode($notification));

            // Store notification in application metadata for persistence
            $this->storeRealTimeNotification($applicationState, $notification);

            // In a real implementation, this would push to WebSocket or SSE
            // For now, we'll use Laravel's broadcasting system (if configured)
            if (config('broadcasting.default') !== 'null') {
                // Broadcast to specific channel for this application
                broadcast(new \App\Events\ApplicationStatusUpdated($applicationState, $notification));
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send real-time notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store real-time notification in metadata
     */
    private function storeRealTimeNotification(ApplicationState $applicationState, array $notification): void
    {
        $metadata = $applicationState->metadata ?? [];
        $metadata['real_time_notifications'] = $metadata['real_time_notifications'] ?? [];

        // Add timestamp if not present
        if (!isset($notification['timestamp'])) {
            $notification['timestamp'] = now()->toIso8601String();
        }

        // Add unique ID if not present
        if (!isset($notification['id'])) {
            $notification['id'] = uniqid('rt_notif_');
        }

        $metadata['real_time_notifications'][] = $notification;

        // Keep only last 50 real-time notifications
        $metadata['real_time_notifications'] = array_slice($metadata['real_time_notifications'], -50);

        $applicationState->metadata = $metadata;
        $applicationState->save();
    }

    /**
     * Send progress update notification
     */
    public function sendProgressUpdateNotification(ApplicationState $applicationState, int $progressPercentage): bool
    {
        try {
            $notification = [
                'type' => 'progress_update',
                'progress' => $progressPercentage,
                'timestamp' => now()->toIso8601String(),
                'session_id' => $applicationState->session_id
            ];

            return $this->sendRealTimeNotification($applicationState, $notification);
        } catch (\Exception $e) {
            Log::error("Failed to send progress update notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark notifications as read
     */
    public function markNotificationsAsRead(ApplicationState $applicationState, array $notificationIds = []): bool
    {
        try {
            $metadata = $applicationState->metadata ?? [];
            $notifications = $metadata['notifications'] ?? [];

            foreach ($notifications as &$notification) {
                if (empty($notificationIds) || in_array($notification['id'], $notificationIds)) {
                    $notification['read'] = true;
                }
            }

            $metadata['notifications'] = $notifications;
            $applicationState->metadata = $metadata;
            $applicationState->save();

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to mark notifications as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send milestone notification
     */
    public function sendMilestoneNotification(ApplicationState $applicationState, string $milestone, array $details = []): bool
    {
        try {
            $metadata = $applicationState->metadata ?? [];
            $metadata['notifications'] = $metadata['notifications'] ?? [];

            // Add milestone notification
            $metadata['notifications'][] = [
                'id' => uniqid('milestone_'),
                'type' => 'info',
                'title' => $this->getMilestoneTitle($milestone),
                'message' => $this->getMilestoneMessage($milestone, $details),
                'timestamp' => now()->toIso8601String(),
                'read' => false,
                'priority' => 'medium',
                'milestone' => $milestone,
                'details' => $details
            ];

            // Mark milestone as completed in metadata
            $metadata[$milestone] = true;
            $metadata[$milestone . '_at'] = now()->toIso8601String();

            // Keep only last 15 notifications
            $metadata['notifications'] = array_slice($metadata['notifications'], -15);

            $applicationState->metadata = $metadata;
            $applicationState->save();

            Log::info("Milestone notification sent for {$applicationState->session_id}: {$milestone}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send milestone notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get milestone title
     */
    private function getMilestoneTitle(string $milestone): string
    {
        $titles = [
            'documents_verified' => 'Documents Verified',
            'credit_check_completed' => 'Credit Check Completed',
            'committee_review_started' => 'Committee Review Started',
            'approval_committee_decision' => 'Committee Decision Made',
            'disbursement_prepared' => 'Disbursement Prepared',
            'funds_disbursed' => 'Funds Disbursed',
            'delivery_started' => 'Delivery Started',
        ];

        return $titles[$milestone] ?? 'Milestone Reached';
    }

    /**
     * Get milestone message
     */
    private function getMilestoneMessage(string $milestone, array $details): string
    {
        $messages = [
            'documents_verified' => 'All your submitted documents have been successfully verified.',
            'credit_check_completed' => 'Your credit assessment has been completed as part of our review process.',
            'committee_review_started' => 'Your application is now being reviewed by our approval committee.',
            'approval_committee_decision' => 'The approval committee has made a decision on your application.',
            'disbursement_prepared' => 'Your loan disbursement is being prepared for transfer.',
            'funds_disbursed' => 'Your loan amount has been successfully transferred to your account.',
            'delivery_started' => 'Your product delivery has been initiated.',
        ];

        $baseMessage = $messages[$milestone] ?? 'A milestone has been reached in your application process.';

        // Add details if provided
        if (!empty($details['message'])) {
            $baseMessage .= ' ' . $details['message'];
        }

        return $baseMessage;
    }

    /**
     * Send batch notifications for multiple applications
     */
    public function sendBatchStatusNotifications(array $applicationUpdates): array
    {
        $results = [];

        foreach ($applicationUpdates as $update) {
            $applicationState = $update['application'];
            $oldStatus = $update['old_status'];
            $newStatus = $update['new_status'];

            $success = $this->sendStatusUpdateNotification($applicationState, $oldStatus, $newStatus);

            $results[] = [
                'session_id' => $applicationState->session_id,
                'success' => $success,
                'status_change' => "{$oldStatus} -> {$newStatus}"
            ];
        }

        return $results;
    }
}

