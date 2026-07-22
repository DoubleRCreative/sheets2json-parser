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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'api' => [
        'openapi_url' => env('API_OPENAPI_URL'),
    ],

    'mcp' => [
        'api_base_url' => env('SHEETS2JSON_API_BASE_URL', env('APP_URL').'/api'),
        'document_path' => env('SHEETS2JSON_DOCUMENT_PATH', '/v1/data/document'),
        'token' => env('SHEETS2JSON_API_TOKEN', ''),
        'timeout' => env('SHEETS2JSON_API_TIMEOUT', 60),
    ],

];
