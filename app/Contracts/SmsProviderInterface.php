<?php

namespace App\Contracts;

interface SmsProviderInterface
{
    /**
     * Send a single SMS
     *
     * @param string $to Recipient phone number
     * @param string $message Message content
     * @return array Response data including success status and message ID
     */
    public function sendSms(string $to, string $message): array;

    /**
     * Send bulk SMS to multiple recipients
     *
     * @param array $recipients List of phone numbers
     * @param string $message Message content
     * @return array Response data including success counts
     */
    public function sendBulkSms(array $recipients, string $message): array;

    /**
     * Format phone number to provider-specific format
     *
     * @param string $phone Raw phone number
     * @return string Formatted phone number
     */
    public function formatPhoneNumber(string $phone): string;

    /**
     * Validate if a phone number is valid for the region
     *
     * @param string $phone Phone number to validate
     * @return bool True if valid
     */
    public function isValidZimbabweNumber(string $phone): bool;
}
