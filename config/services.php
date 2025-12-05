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

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'api_key_sid' => env('TWILIO_API_KEY_SID'),
        'api_key_secret' => env('TWILIO_API_KEY_SECRET'),
        'from' => env('TWILIO_FROM'),
    ],

    'rapiwha' => [
        'api_key' => env('RAPIWHA_API_KEY'),
        'api_url' => env('RAPIWHA_API_URL', 'https://panel.rapiwha.com'),
    ],

    'didit' => [
        'api_key' => env('DIDIT_API_KEY'),
        'app_id' => env('DIDIT_APP_ID'),
        'api_url' => env('DIDIT_API_URL', 'https://verification.didit.me'),
    ],

    'paynow' => [
        'integration_id' => env('PAYNOW_INTEGRATION_ID'),
        'integration_key' => env('PAYNOW_INTEGRATION_KEY'),
        'api_url' => env('PAYNOW_API_URL', 'https://www.paynow.co.zw'),
        'return_url' => env('PAYNOW_RETURN_URL'),
        'result_url' => env('PAYNOW_RESULT_URL'),
    ],

    'ecocash' => [
        'merchant_code' => env('ECOCASH_MERCHANT_CODE'),
        'merchant_key' => env('ECOCASH_MERCHANT_KEY'),
        'api_url' => env('ECOCASH_API_URL', 'https://api.ecocash.co.zw'),
    ],

];
