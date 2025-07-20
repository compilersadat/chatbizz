<?php

namespace App\Helpers;

class FcmHelper
{
    /**
     * Send a Firebase Cloud Messaging notification
     *
     * @param string $deviceToken  Target FCM device token
     * @param string $title        Notification title
     * @param string $body         Notification body
     * @param array  $data         Additional data payload (optional)
     * @return bool
     */
    public static function send($deviceToken, $title, $body, $data = [])
    {
        $SERVER_API_KEY = env('FCM_SERVER_KEY');
        $url = 'https://fcm.googleapis.com/fcm/send';

        $fields = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default'
            ],
            'data' => $data
        ];
        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);

        // Optional: log or check the $result for success
        return true;
    }
}
