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
        // Alphanumeric Sender ID for SMS branding (one-way messaging)
        'alpha_sender_id' => env('TWILIO_ALPHA_SENDER', 'BANCOSYSTEM'),
        // WhatsApp configuration (DEPRECATED - use whatsapp_cloud instead)
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM', 'whatsapp:+14155238886'), // Sandbox default
        'whatsapp_business_id' => env('TWILIO_WHATSAPP_BUSINESS_ID'),
    ],

    // WhatsApp Cloud API (Direct Meta Integration)
    'whatsapp_cloud' => [
        'api_token' => env('WHATSAPP_CLOUD_API_TOKEN'),
        'phone_number_id' => env('WHATSAPP_CLOUD_API_PHONE_NUMBER_ID'),
        'business_id' => env('WHATSAPP_CLOUD_API_BUSINESS_ID'),
        'verify_token' => env('WHATSAPP_CLOUD_API_VERIFY_TOKEN', 'zb_bancosystem_whatsapp_verify'),
        'api_version' => env('WHATSAPP_CLOUD_API_VERSION', 'v18.0'),
        'api_url' => 'https://graph.facebook.com',
    ],

    // DEPRECATED: Switched to Twilio WhatsApp on 2025-12-06
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
