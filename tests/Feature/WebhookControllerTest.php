<?php

declare(strict_types=1);

use Hei\SocialBu\Events\AccountStatusChanged;
use Hei\SocialBu\Events\PostStatusChanged;
use Hei\SocialBu\Webhooks\WebhookController;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Register webhook routes directly for testing since config is set at boot time
    Route::prefix('webhooks/socialbu')
        ->middleware(['api'])
        ->group(function () {
            Route::post('/post', [WebhookController::class, 'handlePost']);
            Route::post('/account', [WebhookController::class, 'handleAccount']);
        });
});

test('post webhook dispatches PostStatusChanged event', function () {
    Event::fake([PostStatusChanged::class]);

    $response = $this->postJson('/webhooks/socialbu/post', [
        'type' => 'post.published',
        'data' => [
            'post_id' => 123,
            'account_id' => 456,
            'status' => 'published',
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['received' => true]);

    Event::assertDispatched(PostStatusChanged::class, function ($event) {
        return $event->postId === 123
            && $event->accountId === 456
            && $event->status === 'published';
    });
});

test('post webhook returns 400 for invalid payload', function () {
    Event::fake([PostStatusChanged::class]);

    $response = $this->postJson('/webhooks/socialbu/post', [
        'type' => 'post.published',
        'data' => [
            // Missing required fields
        ],
    ]);

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Invalid payload']);

    Event::assertNotDispatched(PostStatusChanged::class);
});

test('account webhook dispatches AccountStatusChanged event', function () {
    Event::fake([AccountStatusChanged::class]);

    $response = $this->postJson('/webhooks/socialbu/account', [
        'type' => 'account.connected',
        'data' => [
            'account_id' => 789,
            'action' => 'connected',
            'type' => 'facebook',
            'name' => 'My Facebook Page',
        ],
    ]);

    $response->assertOk();
    $response->assertJson(['received' => true]);

    Event::assertDispatched(AccountStatusChanged::class, function ($event) {
        return $event->accountId === 789
            && $event->action === 'connected'
            && $event->accountType === 'facebook'
            && $event->accountName === 'My Facebook Page';
    });
});

test('account webhook returns 400 for invalid payload', function () {
    Event::fake([AccountStatusChanged::class]);

    $response = $this->postJson('/webhooks/socialbu/account', [
        'type' => 'account.connected',
        'data' => [
            // Missing required fields
        ],
    ]);

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Invalid payload']);

    Event::assertNotDispatched(AccountStatusChanged::class);
});

test('webhook payload parsing works correctly', function () {
    Event::fake([PostStatusChanged::class]);

    // Test with nested data structure
    $response = $this->postJson('/webhooks/socialbu/post', [
        'event' => 'post.status',
        'data' => [
            'post_id' => 999,
            'account_id' => 888,
            'status' => 'failed',
            'error' => 'Rate limited',
        ],
    ]);

    $response->assertOk();

    Event::assertDispatched(PostStatusChanged::class, function ($event) {
        return $event->postId === 999
            && $event->status === 'failed'
            && $event->payload['error'] === 'Rate limited';
    });
});
