<?php

namespace App\Services;

use App\Exceptions\PDF\PDFException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

/**
 * Service for logging PDF generation events and errors
 */
class PDFLoggingService
{
    /**
     * Log channels
     */
    const CHANNEL_INFO = 'pdf_info';

    const CHANNEL_ERROR = 'pdf_error';

    const CHANNEL_DEBUG = 'pdf_debug';

    /**
     * Log a PDF generation event
     *
     * @param  string  $message  The log message
     * @param  array  $context  Additional context information
     */
    public function logInfo(string $message, array $context = []): void
    {
        // Add timestamp to context
        $context['timestamp'] = now()->toISOString();

        // Log to both the default channel and the PDF-specific channel
        Log::info("PDF: {$message}", $context);

        // Log to PDF-specific channel if configured
        if ($this->isChannelConfigured(self::CHANNEL_INFO)) {
            Log::channel(self::CHANNEL_INFO)->info($message, $context);
        }
    }

    /**
     * Log a PDF generation error
     *
     * @param  string  $message  The error message
     * @param  array  $context  Additional context information
     * @param  \Throwable|null  $exception  The exception that occurred
     */
    public function logError(string $message, array $context = [], ?\Throwable $exception = null): void
    {
        // Add timestamp to context
        $context['timestamp'] = now()->toISOString();

        // Add exception details if provided
        if ($exception) {
            $context['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];

            // Add PDFException specific context if applicable
            if ($exception instanceof PDFException) {
                $context['error_code'] = $exception->getErrorCode();
                $context['error_context'] = $exception->getContext();
            }
        }

        // Log to both the default channel and the PDF-specific channel
        Log::error("PDF ERROR: {$message}", $context);

        // Log to PDF-specific channel if configured
        if ($this->isChannelConfigured(self::CHANNEL_ERROR)) {
            Log::channel(self::CHANNEL_ERROR)->error($message, $context);
        }

        // Send notification for critical errors if configured
        if ($this->shouldNotify($context)) {
            $this->sendErrorNotification($message, $context, $exception);
        }
    }

    /**
     * Log detailed debug information for PDF operations
     *
     * @param  string  $message  The debug message
     * @param  array  $context  Additional context information
     */
    public function logDebug(string $message, array $context = []): void
    {
        // Add timestamp to context
        $context['timestamp'] = now()->toISOString();

        // Log to both the default channel and the PDF-specific channel
        Log::debug("PDF DEBUG: {$message}", $context);

        // Log to PDF-specific channel if configured
        if ($this->isChannelConfigured(self::CHANNEL_DEBUG)) {
            Log::channel(self::CHANNEL_DEBUG)->debug($message, $context);
        }
    }

    /**
     * Log a PDF generation event with performance metrics
     *
     * @param  string  $message  The log message
     * @param  float  $duration  The duration of the operation in seconds
     * @param  array  $context  Additional context information
     */
    public function logPerformance(string $message, float $duration, array $context = []): void
    {
        // Add performance metrics to context
        $context['performance'] = [
            'duration_seconds' => $duration,
            'duration_ms' => $duration * 1000,
        ];

        // Add timestamp to context
        $context['timestamp'] = now()->toISOString();

        // Log to both the default channel and the PDF-specific channel
        Log::info("PDF PERFORMANCE: {$message}", $context);

        // Log to PDF-specific channel if configured
        if ($this->isChannelConfigured(self::CHANNEL_INFO)) {
            Log::channel(self::CHANNEL_INFO)->info("PERFORMANCE: {$message}", $context);
        }
    }

    /**
     * Check if a specific log channel is configured
     *
     * @param  string  $channel  The channel name
     * @return bool True if the channel is configured, false otherwise
     */
    private function isChannelConfigured(string $channel): bool
    {
        return Config::has("logging.channels.{$channel}");
    }

    /**
     * Determine if a notification should be sent for this error
     *
     * @param  array  $context  The error context
     * @return bool True if a notification should be sent, false otherwise
     */
    private function shouldNotify(array $context): bool
    {
        // Check if notifications are enabled
        if (! Config::get('services.pdf.notifications.enabled', false)) {
            return false;
        }

        // Check if this is a critical error
        $isCritical = $context['critical'] ?? false;

        // Check if error code is in the list of notifiable errors
        $notifiableErrors = Config::get('services.pdf.notifications.error_codes', []);
        $errorCode = $context['error_code'] ?? '';

        return $isCritical || in_array($errorCode, $notifiableErrors);
    }

    /**
     * Send a notification for a critical error
     *
     * @param  string  $message  The error message
     * @param  array  $context  The error context
     * @param  \Throwable|null  $exception  The exception that occurred
     */
    private function sendErrorNotification(string $message, array $context, ?\Throwable $exception = null): void
    {
        try {
            // Get notification recipients
            $recipients = Config::get('services.pdf.notifications.recipients', []);

            if (empty($recipients)) {
                return;
            }

            // Prepare notification data
            $data = [
                'subject' => "PDF Generation Error: {$message}",
                'message' => $message,
                'context' => $context,
                'exception' => $exception ? [
                    'class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ] : null,
                'timestamp' => now()->toISOString(),
                'environment' => app()->environment(),
            ];

            // Get notification channels from config
            $channels = Config::get('services.pdf.notifications.channels', ['mail']);

            // Send email notifications
            if (in_array('mail', $channels)) {
                foreach ($recipients as $recipient) {
                    Mail::raw("PDF Generation Error: {$message}\n\n".
                        "Error Details:\n".
                        '- Time: '.now()->toDateTimeString()."\n".
                        '- Environment: '.app()->environment()."\n".
                        '- Error Code: '.($context['error_code'] ?? 'N/A')."\n".
                        '- Exception: '.($exception ? get_class($exception) : 'N/A')."\n".
                        '- Message: '.($exception ? $exception->getMessage() : $message)."\n\n".
                        "Context Information:\n".json_encode($context, JSON_PRETTY_PRINT),
                        function ($mail) use ($recipient, $message) {
                            $mail->to($recipient)
                                ->subject("PDF Generation Error: {$message}");
                        }
                    );
                }
            }

            // Send Slack notifications if configured
            if (in_array('slack', $channels) && Config::has('services.pdf.notifications.slack_webhook')) {
                $webhook = Config::get('services.pdf.notifications.slack_webhook');

                // Format message for Slack
                $slackMessage = [
                    'text' => "PDF Generation Error: {$message}",
                    'attachments' => [
                        [
                            'color' => '#FF0000',
                            'fields' => [
                                [
                                    'title' => 'Environment',
                                    'value' => app()->environment(),
                                    'short' => true,
                                ],
                                [
                                    'title' => 'Error Code',
                                    'value' => $context['error_code'] ?? 'N/A',
                                    'short' => true,
                                ],
                                [
                                    'title' => 'Time',
                                    'value' => now()->toDateTimeString(),
                                    'short' => true,
                                ],
                                [
                                    'title' => 'Exception',
                                    'value' => $exception ? get_class($exception) : 'N/A',
                                    'short' => true,
                                ],
                                [
                                    'title' => 'Message',
                                    'value' => $exception ? $exception->getMessage() : $message,
                                    'short' => false,
                                ],
                            ],
                        ],
                    ],
                ];

                // Send to Slack webhook
                $client = new \GuzzleHttp\Client;
                $client->post($webhook, [
                    'json' => $slackMessage,
                    'headers' => ['Content-Type' => 'application/json'],
                ]);
            }

            // Log that notification was sent
            Log::info('PDF error notification sent', [
                'recipients' => $recipients,
                'channels' => $channels,
                'error' => $message,
            ]);
        } catch (\Exception $e) {
            // Log notification failure but don't throw
            Log::error('Failed to send PDF error notification', [
                'error' => $e->getMessage(),
                'original_error' => $message,
            ]);
        }
    }
}
