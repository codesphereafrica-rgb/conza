<?php

return [

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'unipay' => [
        'base_url' => env('UNIPAY_BASE_URL', 'https://unipay-api.onrender.com'),
        'api_key' => env('UNIPAY_API_KEY'),
        'currency' => env('UNIPAY_CURRENCY', 'CDF'),
    ],

    'easypay' => [
        'cid' => env('EASYPAY_CID'),
        'token' => env('EASYPAY_TOKEN'),
        'version' => env('EASYPAY_VERSION', 'Sandbox'),
        'base_url' => 'https://www.e-com-easypay.com',
    ],

    'pawapay' => [
        'api_key' => env('PAWAPAY_API_KEY'),
        'base_url' => env('PAWAPAY_BASE_URL', 'https://api.sandbox.pawapay.io'),
        'webhook_secret' => env('PAWAPAY_WEBHOOK_SECRET'),
        'timeout' => env('PAWAPAY_TIMEOUT', 20),
    ],

];
