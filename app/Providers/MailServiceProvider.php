<?php

namespace App\Providers;

use App\Services\Mail\GmailOAuthTokenManager;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GmailOAuthTokenManager::class);
    }

    public function boot(): void
    {
        Mail::extend('gmail', function (array $config) {
            $username = $config['username'] ?? null;

            if (! $username) {
                throw new \RuntimeException('Gmail mailer requires GMAIL_USERNAME (sender address).');
            }

            $tokenManager = $this->app->make(GmailOAuthTokenManager::class);

            $transport = new EsmtpTransport('smtp.gmail.com', 465, true);
            $transport->setUsername($username);
            $transport->setPassword($tokenManager->getAccessToken());
            $transport->setAuthenticators([new XOAuth2Authenticator()]);

            return $transport;
        });
    }
}
