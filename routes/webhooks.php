<?php

declare(strict_types=1);

use Hei\SocialBu\Webhooks\WebhookController;
use Illuminate\Support\Facades\Route;

$prefix = config('socialbu.webhooks.prefix', 'webhooks/socialbu');
$middleware = config('socialbu.webhooks.middleware', ['api']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->group(function () {
        Route::post('/post', [WebhookController::class, 'handlePost'])
            ->name('socialbu.webhooks.post');

        Route::post('/account', [WebhookController::class, 'handleAccount'])
            ->name('socialbu.webhooks.account');
    });
