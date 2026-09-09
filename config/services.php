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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    // QAD SOAP — creates a real Purchase Requisition (SDI_CreatePR) when
    // Warehouse builds a PR. Distinct from config/qad.php (item master
    // sync, read-only) — this hits the QXtend broker, not the WSA one.
    'qad_soap' => [
        'url'      => env('QAD_SOAP_URL', 'http://qadeesdi.site:24079/qxi/services/QdocWebService'),
        'username' => env('QAD_SOAP_USERNAME', 'mfg'),
        'password' => env('QAD_SOAP_PASSWORD', '54nk31Dharma2025..!'),
        'timeout'  => env('QAD_SOAP_TIMEOUT', 30),
    ],

];
