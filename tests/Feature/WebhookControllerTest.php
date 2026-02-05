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

    // Per spec: flat payload with post_id, account_id, status
    $response = $this->postJson('/webhooks/socialbu/post', [
        'post_id' => 123,
        'account_id' => 456,
        'status' => 'published',
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
        // Missing required fields
    ]);

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Invalid payload']);

    Event::assertNotDispatched(PostStatusChanged::class);
});

test('account webhook dispatches AccountStatusChanged event', function () {
    Event::fake([AccountStatusChanged::class]);

    // Per spec: flat payload with account_action, account_id, account_type, account_name
    $response = $this->postJson('/webhooks/socialbu/account', [
        'account_id' => 789,
        'account_action' => 'connected',
        'account_type' => 'facebook',
        'account_name' => 'My Facebook Page',
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
        // Missing required fields
    ]);

    $response->assertStatus(400);
    $response->assertJson(['error' => 'Invalid payload']);

    Event::assertNotDispatched(AccountStatusChanged::class);
});

test('post webhook handles nested data wrapper for backwards compatibility', function () {
    Event::fake([PostStatusChanged::class]);

    // Some integrations may wrap payload in a 'data' key
    $response = $this->postJson('/webhooks/socialbu/post', [
        'data' => [
            'post_id' => 999,
            'account_id' => 888,
            'status' => 'failed',
        ],
    ]);

    $response->assertOk();

    Event::assertDispatched(PostStatusChanged::class, function ($event) {
        return $event->postId === 999
            && $event->status === 'failed';
    });
});

test('webhook rejects request with invalid signature when secret is configured', function () {
    Event::fake([PostStatusChanged::class]);

    config(['socialbu.webhooks.secret' => 'my-webhook-secret']);

    $response = $this->postJson('/webhooks/socialbu/post', [
        'post_id' => 123,
        'account_id' => 456,
        'status' => 'published',
    ]);

    $response->assertStatus(403);
    $response->assertJson(['error' => 'Invalid signature']);

    Event::assertNotDispatched(PostStatusChanged::class);
});

test('webhook accepts request with valid signature', function () {
    Event::fake([PostStatusChanged::class]);

    $secret = 'my-webhook-secret';
    config(['socialbu.webhooks.secret' => $secret]);

    $payload = json_encode([
        'post_id' => 123,
        'account_id' => 456,
        'status' => 'published',
    ]);

    $signature = hash_hmac('sha256', $payload, $secret);

    $response = $this->postJson('/webhooks/socialbu/post', json_decode($payload, true), [
        'X-SocialBu-Signature' => $signature,
    ]);

    $response->assertOk();

    Event::assertDispatched(PostStatusChanged::class);
});

test('webhook passes without signature when no secret is configured', function () {
    Event::fake([PostStatusChanged::class]);

    config(['socialbu.webhooks.secret' => null]);

    $response = $this->postJson('/webhooks/socialbu/post', [
        'post_id' => 123,
        'account_id' => 456,
        'status' => 'published',
    ]);

    $response->assertOk();

    Event::assertDispatched(PostStatusChanged::class);
});
