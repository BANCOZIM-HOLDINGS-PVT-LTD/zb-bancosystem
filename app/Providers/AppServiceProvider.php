<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Contracts\PDFGeneratorInterface;
use App\Contracts\ApplicationStateRepositoryInterface;
use App\Services\PDFGeneratorService;
use App\Repositories\ApplicationStateRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind interfaces to implementations
        $this->app->bind(PDFGeneratorInterface::class, PDFGeneratorService::class);
        $this->app->bind(ApplicationStateRepositoryInterface::class, ApplicationStateRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        \App\Models\ApplicationState::observe(\App\Observers\ApplicationStateObserver::class);

        // Force HTTPS in production
        if (app()->environment('production') || env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        // Validate critical environment variables in production
        if (app()->environment('production')) {
            $this->validateProductionEnvironment();
        }

        // Validate Twilio credentials if WhatsApp is enabled
        $this->validateTwilioCredentials();
    }

    /**
     * Validate critical environment variables for production
     */
    private function validateProductionEnvironment(): void
    {
        // APP_KEY is always required
        if (empty(env('APP_KEY'))) {
            Log::emergency("Missing required environment variable: APP_KEY");
            throw new \RuntimeException("Missing required environment variable: APP_KEY");
        }

        // DB_PASSWORD and REDIS_PASSWORD are optional for SQLite/local Redis
        $optionalVars = ['DB_PASSWORD', 'REDIS_PASSWORD'];
        foreach ($optionalVars as $var) {
            if (env($var) === null) {
                Log::warning("Environment variable {$var} is not set");
            }
        }

        // Validate APP_KEY format
        if (strlen(env('APP_KEY')) < 32) {
            Log::emergency('APP_KEY must be at least 32 characters long');
            throw new \RuntimeException('APP_KEY must be at least 32 characters long');
        }
    }

    /**
     * Validate Twilio credentials
     */
    private function validateTwilioCredentials(): void
    {
        $twilioSid = env('TWILIO_ACCOUNT_SID');
        $twilioToken = env('TWILIO_AUTH_TOKEN');
        $twilioFrom = env('TWILIO_WHATSAPP_FROM');

        // Check if any Twilio config is set
        if ($twilioSid || $twilioToken || $twilioFrom) {
            // If any is set, all must be set
            if (empty($twilioSid) || empty($twilioToken) || empty($twilioFrom)) {
                Log::warning('Incomplete Twilio configuration. WhatsApp features may not work.');
            }

            // Validate format
            if ($twilioSid && !str_starts_with($twilioSid, 'AC')) {
                Log::warning('TWILIO_ACCOUNT_SID should start with "AC"');
            }

            if ($twilioFrom && !str_starts_with($twilioFrom, 'whatsapp:+')) {
                Log::warning('TWILIO_WHATSAPP_FROM should start with "whatsapp:+"');
            }

            // Check for example/placeholder values
            $placeholderValues = [
                'your_twilio_account_sid_here',
                'your_twilio_auth_token_here',
                'whatsapp:+your_whatsapp_number_here'
            ];

            if (in_array($twilioSid, $placeholderValues) ||
                in_array($twilioToken, $placeholderValues) ||
                in_array($twilioFrom, $placeholderValues)) {
                Log::warning('Twilio credentials appear to be placeholder values');
            }
        }
    }
}
