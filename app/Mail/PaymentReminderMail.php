<?php

namespace App\Mail;

use App\Models\ApplicationState;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ApplicationState $application,
        public string $stage,
        public string $resumeLink
    ) {
    }

    public function envelope(): Envelope
    {
        $ref = $this->application->reference_code ?? $this->application->session_id;

        return new Envelope(subject: "Payment Reminder - {$ref}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-reminder');
    }
}
