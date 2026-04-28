<?php

namespace App\Helpers;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmHelper
{
    /**
     * Send a Firebase Cloud Messaging notification (HTTP v1 API)
     * 
     * @param string $deviceToken The FCM device token
     * @param string $title       Notification title
     * @param string $body        Notification body
     * @param array  $data        Custom data payload (optional)
     * @return array|bool         API response array or false on error
     */
    public static function send($deviceToken, $title, $body, $data = [])
    {
        $credentialsFilePath = self::resolveCredentialsPath();

        if (! $credentialsFilePath) {
            Log::error('FCM credentials file is not configured.');
            return false;
        }

        if (! is_file($credentialsFilePath)) {
            Log::error('FCM credentials file was not found.', [
                'path' => $credentialsFilePath,
            ]);
            return false;
        }

        $projectId = self::resolveProjectId($credentialsFilePath);

        if (! $projectId) {
            Log::error('FCM project ID could not be resolved.');
            return false;
        }

        // Get Google OAuth2 access token using service account
        $client = new GoogleClient();
        $client->setAuthConfig($credentialsFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();
        if (!$token || !isset($token['access_token'])) {
            Log::error('FCM access token could not be generated.');
            return false;
        }

        $message = [
            "token" => $deviceToken,
            "notification" => [
                "title" => $title,
                "body" => $body,
            ],
            "android" => [
                "priority" => "high",
                "notification" => [
                    "sound" => "default",
                ],
            ],
            "apns" => [
                "headers" => [
                    "apns-priority" => "10",
                ],
                "payload" => [
                    "aps" => [
                        "sound" => "default",
                        "content-available" => 1,
                    ],
                ],
            ],
        ];

        // Only include data if provided and non-empty
        if (!empty($data)) {
            $message["data"] = self::normalizeDataPayload($data);
        }

        try {
            $response = Http::withToken($token['access_token'])
                ->timeout(15)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::error('FCM request failed.', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        $decoded = $response->json();

        if (! $response->successful()) {
            Log::error('FCM send failed.', [
                'status' => $response->status(),
                'response' => $decoded ?: $response->body(),
                'project_id' => $projectId,
            ]);
            return false;
        }

        // Optionally log the response for debugging
        Log::info('FCM HTTP v1 Response:', [
            'project_id' => $projectId,
            'response' => $decoded,
        ]);

        return $decoded;
    }

    protected static function resolveCredentialsPath(): ?string
    {
        $path = config('services.firebase.credentials');

        if (! $path) {
            return null;
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    protected static function resolveProjectId(string $credentialsFilePath): ?string
    {
        $configuredProjectId = config('services.firebase.project_id');

        if ($configuredProjectId) {
            return $configuredProjectId;
        }

        $credentials = json_decode(file_get_contents($credentialsFilePath), true);

        return $credentials['project_id'] ?? null;
    }

    protected static function normalizeDataPayload(array $data): array
    {
        return array_map(static function ($value): string {
            if (is_null($value)) {
                return '';
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if (is_scalar($value)) {
                return (string) $value;
            }

            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $encoded === false ? '' : $encoded;
        }, $data);
    }
}
