<?php

namespace App\Services\Mail;

use App\Exceptions\GmailOAuthException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GmailOAuthTokenManager
{
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const CACHE_KEY = 'gmail.oauth.access_token';
    private const CACHE_TTL_SECONDS = 3300;

    public function getAccessToken(): string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn () => $this->refresh());
    }

    public function forgetCachedToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function refresh(): string
    {
        $clientId = config('mail.mailers.gmail.client_id');
        $clientSecret = config('mail.mailers.gmail.client_secret');
        $refreshToken = config('mail.mailers.gmail.refresh_token');

        if (! $clientId || ! $clientSecret || ! $refreshToken) {
            throw new GmailOAuthException(
                'Gmail OAuth credentials missing. Set GMAIL_OAUTH_CLIENT_ID, GMAIL_OAUTH_CLIENT_SECRET, GMAIL_OAUTH_REFRESH_TOKEN.'
            );
        }

        $response = Http::asForm()->post(self::TOKEN_ENDPOINT, [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new GmailOAuthException(
                'Gmail OAuth token refresh failed: '.$response->status().' '.$response->body()
            );
        }

        $accessToken = $response->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new GmailOAuthException('Gmail OAuth token endpoint returned no access_token: '.$response->body());
        }

        return $accessToken;
    }
}
