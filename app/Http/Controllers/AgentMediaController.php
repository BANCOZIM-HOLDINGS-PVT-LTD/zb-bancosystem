<?php

namespace App\Http\Controllers;

use App\Models\AgentApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AgentMediaController extends Controller
{
    /**
     * Proxy an agent's ID image, handling Twilio-authenticated URLs and local storage paths.
     * Route: GET /admin/agent-media/{agent}/{side}
     *   side = 'front' | 'back'
     */
    public function show(AgentApplication $agent, string $side = 'front')
    {
        $url = $side === 'back' ? $agent->id_back_url : $agent->id_front_url;

        if (!$url) {
            abort(404, 'No image uploaded');
        }

        // Local storage path stored as "agent-ids/filename.jpg"
        if (!str_starts_with($url, 'http')) {
            if (Storage::disk('public')->exists($url)) {
                return response()->file(Storage::disk('public')->path($url));
            }
            abort(404, 'Image not found in storage');
        }

        // Placeholder stored when Cloud API media content was not saved
        if (str_starts_with($url, 'cloud_api_media_')) {
            abort(404, 'Image was not downloaded at upload time');
        }

        // Twilio media URL — fetch with HTTP Basic Auth
        if (str_contains($url, 'api.twilio.com')) {
            $sid   = config('services.twilio.account_sid');
            $token = config('services.twilio.auth_token');

            if (!$sid || !$token) {
                abort(503, 'Twilio credentials not configured');
            }

            $response = Http::withBasicAuth($sid, $token)->get($url);

            if (!$response->successful()) {
                Log::warning('Failed to proxy Twilio media', [
                    'url'    => $url,
                    'status' => $response->status(),
                ]);
                abort(502, 'Could not retrieve image from Twilio');
            }

            $contentType = $response->header('Content-Type') ?? 'image/jpeg';

            return response($response->body(), 200)
                ->header('Content-Type', $contentType)
                ->header('Cache-Control', 'private, max-age=3600');
        }

        // Generic external URL — stream directly
        $response = Http::get($url);
        if (!$response->successful()) {
            abort(502, 'Could not retrieve image');
        }

        $contentType = $response->header('Content-Type') ?? 'image/jpeg';
        return response($response->body(), 200)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'private, max-age=3600');
    }
}
