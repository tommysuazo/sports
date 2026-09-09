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

    'digital_sports_tech' => [
        'base_url' => env('DIGITAL_SPORTS_TECH_BASE_URL', 'https://bv2-us.digitalsportstech.com/api'),
        'referer' => env('DIGITAL_SPORTS_TECH_REFERER', 'https://bv2-us.digitalsportstech.com/betbuilder'),
        'sportsbook_alias' => env('DIGITAL_SPORTS_TECH_SPORTSBOOK_ALIAS', 'juancito'),
        'accept_language' => env('DIGITAL_SPORTS_TECH_ACCEPT_LANGUAGE', 'es-ES,es;q=0.9'),
        'user_agent' => env(
            'DIGITAL_SPORTS_TECH_USER_AGENT',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36'
        ),
        'challenge_timeout' => env('DIGITAL_SPORTS_TECH_CHALLENGE_TIMEOUT', 10),
        'challenge_ttl' => env('DIGITAL_SPORTS_TECH_CHALLENGE_TTL', 300),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
