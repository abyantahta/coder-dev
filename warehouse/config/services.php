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

    'qxtend' => [
        'base_url'    => env('QXTEND_BASE_URL', ''),
        'username'    => env('QXTEND_USERNAME', ''),
        'password'    => env('QXTEND_PASSWORD', ''),
        'domain'      => env('QXTEND_DOMAIN', 'QAD'),
        'browse_url'  => env('QXTEND_BROWSE_URL', ''),
        'timeout'     => env('QXTEND_TIMEOUT', 30),
    ],

    'qad_soap' => [
        'url'           => env('QAD_SOAP_URL', ''),
        'username'      => env('QAD_SOAP_USERNAME', ''),
        'password'      => env('QAD_SOAP_PASSWORD', ''),
        'timeout'       => env('QAD_SOAP_TIMEOUT', 30),
        'wsa_url'       => env('QAD_WSA_URL', ''),
        'wsa_namespace' => env('QAD_WSA_NAMESPACE', ''),
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', ''),
        'api_url'  => env('WHATSAPP_API_URL', ''),
        'api_key'  => env('WHATSAPP_API_KEY', ''),
        'sender'   => env('WHATSAPP_SENDER', ''),
        'timeout'  => env('WHATSAPP_TIMEOUT', 15),
    ],

];
