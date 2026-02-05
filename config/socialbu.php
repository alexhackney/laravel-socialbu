<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | SocialBu API Token
    |--------------------------------------------------------------------------
    |
    | Your SocialBu API authentication token. Get this from your SocialBu
    | account settings or by calling the /auth/get_token endpoint.
    |
    */
    'token' => env('SOCIALBU_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Default Account IDs
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of social account IDs to post to by default.
    | These can be overridden per-post using the fluent builder.
    |
    */
    'account_ids' => array_map(
        'intval',
        array_filter(
            explode(',', env('SOCIALBU_ACCOUNT_IDS', '')),
            fn ($value) => $value !== '' && $value !== null
        )
    ),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the SocialBu API. You shouldn't need to change this
    | unless you're testing against a different environment.
    |
    */
    'base_url' => env('SOCIALBU_BASE_URL', 'https://socialbu.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable the webhook routes. When enabled, routes will be
    | registered at the configured prefix for post and account callbacks.
    |
    */
    'webhooks' => [
        'enabled' => env('SOCIALBU_WEBHOOKS_ENABLED', false),
        'prefix' => env('SOCIALBU_WEBHOOKS_PREFIX', 'webhooks/socialbu'),
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Options
    |--------------------------------------------------------------------------
    |
    | Options passed to the underlying HTTP client.
    |
    */
    'http' => [
        'timeout' => env('SOCIALBU_TIMEOUT', 30),
        'connect_timeout' => env('SOCIALBU_CONNECT_TIMEOUT', 10),
    ],
];
