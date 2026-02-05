<?php

declare(strict_types=1);

use Hei\SocialBu\Client\SocialBuClient;
use Hei\SocialBu\Exceptions\AuthenticationException;
use Hei\SocialBu\Exceptions\NotFoundException;
use Hei\SocialBu\Exceptions\RateLimitException;
use Hei\SocialBu\Exceptions\ServerException;
use Hei\SocialBu\Exceptions\SocialBuException;
use Hei\SocialBu\Exceptions\ValidationException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new SocialBuClient(
        token: 'test-token',
        accountIds: [123, 456],
        baseUrl: 'https://socialbu.com/api/v1',
    );
});

test('it makes GET requests with bearer token', function () {
    Http::fake([
        'socialbu.com/api/v1/accounts*' => Http::response(['data' => []], 200),
    ]);

    $this->client->get('/accounts');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer test-token')
            && $request->method() === 'GET'
            && str_contains($request->url(), '/accounts');
    });
});

test('it makes POST requests with data', function () {
    Http::fake([
        'socialbu.com/api/v1/posts*' => Http::response(['id' => 1], 200),
    ]);

    $this->client->post('/posts', ['content' => 'Hello']);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['content'] === 'Hello';
    });
});

test('it makes PATCH requests', function () {
    Http::fake([
        'socialbu.com/api/v1/posts/1*' => Http::response(['id' => 1], 200),
    ]);

    $this->client->patch('/posts/1', ['content' => 'Updated']);

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && $request['content'] === 'Updated';
    });
});

test('it makes DELETE requests', function () {
    Http::fake([
        'socialbu.com/api/v1/posts/1*' => Http::response([], 200),
    ]);

    $this->client->delete('/posts/1');

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE';
    });
});

test('it throws AuthenticationException on 401', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Unauthorized'], 401),
    ]);

    $this->client->get('/accounts');
})->throws(AuthenticationException::class, 'Unauthorized');

test('it throws NotFoundException on 404', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Not found'], 404),
    ]);

    $this->client->get('/posts/999');
})->throws(NotFoundException::class, 'Not found');

test('it throws ValidationException on 422 with errors', function () {
    Http::fake([
        '*' => Http::response([
            'message' => 'Validation failed',
            'errors' => ['content' => ['Content is required']],
        ], 422),
    ]);

    try {
        $this->client->post('/posts', []);
    } catch (ValidationException $e) {
        expect($e->errors())->toBe(['content' => ['Content is required']]);

        throw $e;
    }
})->throws(ValidationException::class, 'Validation failed');

test('it throws RateLimitException on 429 with retry-after', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Too many requests'], 429, [
            'Retry-After' => '60',
        ]),
    ]);

    try {
        $this->client->get('/posts');
    } catch (RateLimitException $e) {
        expect($e->retryAfter())->toBe(60);

        throw $e;
    }
})->throws(RateLimitException::class, 'Too many requests');

test('it throws ServerException on 5xx errors', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Internal error'], 500),
    ]);

    $this->client->get('/posts');
})->throws(ServerException::class, 'Internal error');

test('it throws SocialBuException on other errors', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Bad request'], 400),
    ]);

    $this->client->get('/posts');
})->throws(SocialBuException::class, 'Bad request');

test('it returns response body on success', function () {
    Http::fake([
        '*' => Http::response(['data' => ['id' => 1, 'name' => 'Test']], 200),
    ]);

    $result = $this->client->get('/accounts');

    expect($result)->toBe(['data' => ['id' => 1, 'name' => 'Test']]);
});

test('isConfigured returns true when token is set', function () {
    expect($this->client->isConfigured())->toBeTrue();
});

test('isConfigured returns false when token is null', function () {
    $client = new SocialBuClient(token: null);

    expect($client->isConfigured())->toBeFalse();
});

test('isConfigured returns false when token is empty', function () {
    $client = new SocialBuClient(token: '');

    expect($client->isConfigured())->toBeFalse();
});

test('getAccountIds returns configured account ids', function () {
    expect($this->client->getAccountIds())->toBe([123, 456]);
});

test('exceptions include context for debugging', function () {
    Http::fake([
        '*' => Http::response(['message' => 'Error', 'detail' => 'info'], 400),
    ]);

    try {
        $this->client->post('/posts', ['content' => 'test']);
    } catch (SocialBuException $e) {
        $context = $e->context();

        expect($context['status_code'])->toBe(400);
        expect($context['response'])->toBe(['message' => 'Error', 'detail' => 'info']);
        expect($context['request']['method'])->toBe('POST');
        expect($context['request']['endpoint'])->toBe('/posts');
        expect($context['request']['data'])->toBe(['content' => 'test']);
    }
});
