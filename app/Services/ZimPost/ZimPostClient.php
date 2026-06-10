<?php

namespace App\Services\ZimPost;

use App\Services\ZimPost\Exceptions\ZimPostApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZimPostClient
{
    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $baseUrl = null,
        protected int $timeout = 10,
    ) {
        $this->apiKey = $apiKey ?? config('services.zimpost.api_key');
        $this->baseUrl = rtrim($baseUrl ?? config('services.zimpost.base_url'), '/');
        $this->timeout = $timeout ?: (int) config('services.zimpost.timeout', 10);
    }

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $body = []): array
    {
        return $this->request('POST', $path, ['json' => $body]);
    }

    protected function request(string $method, string $path, array $options): array
    {
        if (empty($this->apiKey)) {
            throw new ZimPostApiException(
                'ZimPost API key is not configured. Set ZIMPOST_API_KEY in your environment.',
                errorCode: 'ERR_012',
                httpStatus: 401,
            );
        }

        $url = $this->baseUrl . '/' . ltrim($path, '/');

        try {
            $response = $this->client()->send($method, $url, $options);
        } catch (\Throwable $e) {
            Log::error('ZimPost HTTP transport failure', [
                'method' => $method,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new ZimPostApiException(
                'ZimPost is unreachable: ' . $e->getMessage(),
                errorCode: 'ERR_022',
                httpStatus: 0,
                previous: $e,
            );
        }

        return $this->parseResponse($response, $method, $path);
    }

    protected function client(): PendingRequest
    {
        return Http::withHeaders([
            'X-Partner-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->retry(3, 500, function (\Throwable $exception, $request) {
                // Retry only on transient errors. Don't retry 4xx (apart from 429, handled by Http::retry).
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            }, throw: false);
    }

    protected function parseResponse(Response $response, string $method, string $path): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $payload = $response->json() ?? [];
        $err = $payload['error'] ?? [];
        $code = $err['code'] ?? null;
        $message = $err['message'] ?? 'ZimPost API error (HTTP ' . $response->status() . ')';
        $field = $err['field'] ?? null;
        $hint = $err['hint'] ?? null;

        Log::warning('ZimPost API error response', [
            'method' => $method,
            'path' => $path,
            'http_status' => $response->status(),
            'error_code' => $code,
            'message' => $message,
            'field' => $field,
        ]);

        throw new ZimPostApiException(
            message: $message,
            errorCode: $code,
            field: $field,
            hint: $hint,
            httpStatus: $response->status(),
        );
    }
}
