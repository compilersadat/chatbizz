<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'cashfree' => [
        'env' => env('CASHFREE_ENV', 'sandbox'),
        'pg' => [
            'client_id' => env('CASHFREE_APP_ID'),
            'secret_key' => env('CASHFREE_SECRET_KEY'),
            'api_version' => env('CASHFREE_API_VERSION', '2023-08-01'),
            'base_url' => env('CASHFREE_PG_BASE_URL', 'https://sandbox.cashfree.com/pg'),
        ],
        'payout' => [
            'client_id' => env('CASHFREE_PAYOUT_CLIENT_ID'),
            'secret_key' => env('CASHFREE_PAYOUT_CLIENT_SECRET'),
            'base_url' => env('CASHFREE_PAYOUT_BASE_URL', 'https://sandbox.cashfree.com/payout'),
        ],
    ],

];
