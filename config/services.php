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

    'sportsblaze' => [
        'key' => env('SPORTSBLAZE_API_KEY'),
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    'perplexity' => [
        'key' => env('PERPLEXITY_API_KEY'),
    ],

    'balldontlie' => [
        'key' => env('BALL_DONT_LIE_API_KEY'),
        'base_url' => 'https://api.balldontlie.io/v1',
    ],

    'espn' => [
        'timeout' => env('ESPN_TIMEOUT', 30),
    ],

    'sportradar' => [
        'key' => env('SPORTRADAR_API_KEY'),
        'access_level' => env('SPORTRADAR_ACCESS_LEVEL', 'trial'),
        'language' => env('SPORTRADAR_LANGUAGE', 'en'),
        'timeout' => env('SPORTRADAR_TIMEOUT', 30),
        'max_retries' => env('SPORTRADAR_MAX_RETRIES', 4),
        'retry_sleep_ms' => env('SPORTRADAR_RETRY_SLEEP_MS', 1200),
    ],

];
