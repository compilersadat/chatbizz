<?php

namespace App\Helpers;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Storage;

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
        $projectId = "chatbizz-b53e3";
        $credentialsFilePath = env('FIREBASE_CREDENTIALS'); // Path to your service account JSON

        // Get Google OAuth2 access token using service account
        $client = new GoogleClient();
        $client->setAuthConfig($credentialsFilePath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->refreshTokenWithAssertion();
        $token = $client->getAccessToken();
        if (!$token || !isset($token['access_token'])) {
            return false;
        }
        $access_token = $token['access_token'];

        $headers = [
            "Authorization: Bearer $access_token",
            'Content-Type: application/json'
        ];

        $message = [
            "token" => $deviceToken,
            "notification" => [
                "title" => $title,
                "body" => $body,
            ],
        ];

        // Only include data if provided and non-empty
        if (!empty($data)) {
            $message["data"] = array_map('strval', $data); // Data values must be string
        }

        $payload = json_encode(["message" => $message]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            \Log::error("FCM cURL error: $err");
            return false;
        }
        $decoded = json_decode($response, true);

        // Optionally log the response for debugging
        \Log::info('FCM HTTP v1 Response:', $decoded);

        return $decoded;
    }
}
