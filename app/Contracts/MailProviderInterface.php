<?php

namespace App\Contracts;

use App\Models\ApplicationState;

interface MailProviderInterface
{
    public function sendApplicationReceivedEmail(ApplicationState $application): bool;

    public function sendApplicationStatusUpdatedEmail(ApplicationState $application): bool;

    public function sendPaymentReceiptEmail(ApplicationState $application, array $paymentData): bool;

    public function sendPaymentReminderEmail(ApplicationState $application, string $stage, string $resumeLink): bool;

    public function sendWelcomeEmail(string $email, string $name): bool;
}
