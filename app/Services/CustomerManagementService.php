<?php

namespace App\Services;

use App\Models\BulkSMSCampaign;
use App\Models\ApplicationState;
use App\Models\DeliveryTracking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerManagementService
{
    private SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send a bulk SMS campaign
     */
    public function sendCampaign(BulkSMSCampaign $campaign): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $recipients = $this->getRecipients($campaign);
            $campaign->update([
                'recipients_count' => count($recipients),
                'recipient_list' => $recipients,
            ]);

            foreach ($recipients as $recipient) {
                $message = $this->personalizeMessage($campaign->message_template, $recipient);
                
                try {
                    $sent = $this->smsService->sendSMS($recipient['phone'], $message);
                    
                    if ($sent) {
                        $results['sent']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Failed to send to {$recipient['phone']}";
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Error sending to {$recipient['phone']}: {$e->getMessage()}";
                }
            }

            $campaign->update([
                'status' => $results['failed'] > 0 && $results['sent'] === 0 ? 'failed' : 'sent',
                'sent_count' => $results['sent'],
                'failed_count' => $results['failed'],
                'sent_at' => now(),
            ]);

            Log::info("Bulk SMS Campaign completed", [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'sent' => $results['sent'],
                'failed' => $results['failed'],
            ]);

        } catch (\Exception $e) {
            Log::error("Bulk SMS Campaign failed", [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            $campaign->update(['status' => 'failed']);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get recipients based on campaign type
     */
    public function getRecipients(BulkSMSCampaign $campaign): array
    {
        return match($campaign->type) {
            BulkSMSCampaign::TYPE_INSTALLMENT_DUE => $this->getInstallmentDueRecipients(),
            BulkSMSCampaign::TYPE_INCOMPLETE_APPLICATION => $this->getIncompleteApplicationRecipients(),
            BulkSMSCampaign::TYPE_BIRTHDAY => $this->getBirthdayRecipients(),
            default => $this->getCustomRecipients($campaign),
        };
    }

    /**
     * Get users with installments due
     */
    protected function getInstallmentDueRecipients(): array
    {
        return DeliveryTracking::query()
            ->join('application_states', 'delivery_trackings.application_state_id', '=', 'application_states.id')
            ->where('delivery_trackings.status', 'delivered')
            ->whereNotNull('delivery_trackings.recipient_phone')
            ->select([
                'delivery_trackings.recipient_name as name',
                'delivery_trackings.recipient_phone as phone',
                'application_states.reference_code as reference',
                'delivery_trackings.product_type as product',
            ])
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name ?? 'Customer',
                'phone' => $row->phone,
                'reference' => $row->reference ?? 'N/A',
                'product' => $row->product ?? 'Product',
            ])
            ->toArray();
    }

    /**
     * Get users with incomplete applications
     */
    protected function getIncompleteApplicationRecipients(): array
    {
        return ApplicationState::query()
            ->where('is_archived', false)
            ->whereNotNull('form_data')
            ->where('current_step', '!=', 'completed')
            ->get()
            ->filter(function ($app) {
                $formData = $app->form_data ?? [];
                return !empty($formData['cellNumber']) || !empty($formData['mobileNumber']);
            })
            ->map(function ($app) {
                $formData = $app->form_data ?? [];
                return [
                    'name' => $formData['fullName'] ?? $formData['applicantName'] ?? 'Customer',
                    'phone' => $formData['cellNumber'] ?? $formData['mobileNumber'] ?? '',
                    'reference' => $app->reference_code ?? 'N/A',
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get users with birthdays (placeholder - needs DOB field in form_data)
     */
    protected function getBirthdayRecipients(): array
    {
        $today = now()->format('m-d');
        
        return ApplicationState::query()
            ->whereNotNull('form_data')
            ->get()
            ->filter(function ($app) use ($today) {
                $formData = $app->form_data ?? [];
                $dob = $formData['dateOfBirth'] ?? null;
                if (!$dob) return false;
                
                try {
                    $birthDate = \Carbon\Carbon::parse($dob);
                    return $birthDate->format('m-d') === $today;
                } catch (\Exception $e) {
                    return false;
                }
            })
            ->filter(function ($app) {
                $formData = $app->form_data ?? [];
                return !empty($formData['cellNumber']) || !empty($formData['mobileNumber']);
            })
            ->map(function ($app) {
                $formData = $app->form_data ?? [];
                return [
                    'name' => $formData['fullName'] ?? $formData['applicantName'] ?? 'Customer',
                    'phone' => $formData['cellNumber'] ?? $formData['mobileNumber'] ?? '',
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get custom recipients from filters
     */
    protected function getCustomRecipients(BulkSMSCampaign $campaign): array
    {
        $cachedList = $campaign->recipient_list;
        if (!empty($cachedList)) {
            return $cachedList;
        }

        // Default: return all users with phone numbers
        return ApplicationState::query()
            ->whereNotNull('form_data')
            ->get()
            ->filter(function ($app) {
                $formData = $app->form_data ?? [];
                return !empty($formData['cellNumber']) || !empty($formData['mobileNumber']);
            })
            ->map(function ($app) {
                $formData = $app->form_data ?? [];
                return [
                    'name' => $formData['fullName'] ?? $formData['applicantName'] ?? 'Customer',
                    'phone' => $formData['cellNumber'] ?? $formData['mobileNumber'] ?? '',
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Personalize message with recipient data
     */
    protected function personalizeMessage(string $template, array $recipient): string
    {
        $replacements = [
            '{name}' => $recipient['name'] ?? 'Customer',
            '{reference}' => $recipient['reference'] ?? '',
            '{amount}' => $recipient['amount'] ?? '',
            '{date}' => $recipient['date'] ?? now()->format('M j, Y'),
            '{holiday}' => $recipient['holiday'] ?? 'the holiday',
            '{product}' => $recipient['product'] ?? 'your product',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
