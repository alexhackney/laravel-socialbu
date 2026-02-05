<?php

declare(strict_types=1);

use Hei\SocialBu\Client\SocialBuClient;
use Hei\SocialBu\Data\PaginatedResponse;
use Hei\SocialBu\Data\Post;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new SocialBuClient(
        token: 'test-token',
        accountIds: [123, 456],
    );
});

test('list returns array of posts', function () {
    Http::fake([
        '*/posts*' => Http::response($this->fixture('posts.json')),
    ]);

    $posts = $this->client->posts()->list();

    expect($posts)->toBeArray();
    expect($posts)->toHaveCount(2);
    expect($posts[0])->toBeInstanceOf(Post::class);
    expect($posts[0]->id)->toBe(101);
    expect($posts[0]->content)->toBe('Hello world!');
});

test('list filters by type', function () {
    Http::fake([
        '*/posts*' => Http::response($this->fixture('posts.json')),
    ]);

    $this->client->posts()->list(type: 'scheduled');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'type=scheduled');
    });
});

test('get returns single post', function () {
    Http::fake([
        '*/posts/101*' => Http::response([
            'data' => [
                'id' => 101,
                'content' => 'Hello world!',
                'status' => 'published',
                'account_ids' => [1, 2],
                'created_at' => '2025-01-15 10:00:00',
            ],
        ]),
    ]);

    $post = $this->client->posts()->get(101);

    expect($post)->toBeInstanceOf(Post::class);
    expect($post->id)->toBe(101);
    expect($post->content)->toBe('Hello world!');
});

test('create sends correct payload', function () {
    Http::fake([
        '*/posts*' => Http::response([
            'data' => [
                'id' => 200,
                'content' => 'New post',
                'status' => 'scheduled',
                'account_ids' => [1, 2],
                'publish_at' => '2025-06-15 14:00:00',
                'created_at' => '2025-01-15 10:00:00',
            ],
        ]),
    ]);

    $post = $this->client->posts()->create(
        content: 'New post',
        accountIds: [1, 2],
        publishAt: '2025-06-15 14:00:00',
        attachments: [['upload_token' => 'token-123']],
        postbackUrl: 'https://example.com/webhook',
    );

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['content'] === 'New post'
            && $request['accounts'] === [1, 2]
            && $request['publish_at'] === '2025-06-15 14:00:00'
            && $request['existing_attachments'] === [['upload_token' => 'token-123']]
            && $request['postback_url'] === 'https://example.com/webhook';
    });

    expect($post)->toBeInstanceOf(Post::class);
    expect($post->id)->toBe(200);
});

test('create with draft flag', function () {
    Http::fake([
        '*/posts*' => Http::response([
            'data' => [
                'id' => 201,
                'content' => 'Draft post',
                'status' => 'draft',
                'account_ids' => [1],
                'created_at' => '2025-01-15 10:00:00',
            ],
        ]),
    ]);

    $this->client->posts()->create(
        content: 'Draft post',
        accountIds: [1],
        draft: true,
    );

    Http::assertSent(function ($request) {
        return $request['draft'] === true;
    });
});

test('update sends patch request', function () {
    Http::fake([
        '*/posts/101*' => Http::response([
            'data' => [
                'id' => 101,
                'content' => 'Updated content',
                'status' => 'scheduled',
                'account_ids' => [1],
                'created_at' => '2025-01-15 10:00:00',
            ],
        ]),
    ]);

    $post = $this->client->posts()->update(101, ['content' => 'Updated content']);

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && $request['content'] === 'Updated content';
    });

    expect($post->content)->toBe('Updated content');
});

test('delete sends delete request', function () {
    Http::fake([
        '*/posts/101*' => Http::response([]),
    ]);

    $result = $this->client->posts()->delete(101);

    Http::assertSent(function ($request) {
        return $request->method() === 'DELETE';
    });

    expect($result)->toBeTrue();
});

test('paginate returns PaginatedResponse', function () {
    Http::fake([
        '*/posts*' => Http::response($this->fixture('posts.json')),
    ]);

    $response = $this->client->posts()->paginate();

    expect($response)->toBeInstanceOf(PaginatedResponse::class);
    expect($response->items)->toHaveCount(2);
    expect($response->items[0])->toBeInstanceOf(Post::class);
});

test('lazy yields posts one at a time', function () {
    Http::fake([
        '*/posts*' => Http::response($this->fixture('posts.json')),
    ]);

    $posts = [];
    foreach ($this->client->posts()->lazy() as $post) {
        $posts[] = $post;
    }

    expect($posts)->toHaveCount(2);
});
