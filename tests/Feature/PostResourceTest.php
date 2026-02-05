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
            'id' => 101,
            'content' => 'Hello world!',
            'status' => 'published',
            'account_ids' => [1, 2],
            'created_at' => '2025-01-15 10:00:00',
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
            'success' => true,
            'posts' => [
                [
                    'id' => 200,
                    'content' => 'New post',
                    'status' => 'scheduled',
                    'account_ids' => [1, 2],
                    'publish_at' => '2025-06-15 14:00:00',
                    'created_at' => '2025-01-15 10:00:00',
                ],
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
            'success' => true,
            'posts' => [
                [
                    'id' => 201,
                    'content' => 'Draft post',
                    'status' => 'draft',
                    'account_ids' => [1],
                    'created_at' => '2025-01-15 10:00:00',
                ],
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
            'success' => true,
            'message' => 'Post updated successfully.',
        ]),
    ]);

    $result = $this->client->posts()->update(101, ['content' => 'Updated content']);

    Http::assertSent(function ($request) {
        return $request->method() === 'PATCH'
            && $request['content'] === 'Updated content';
    });

    expect($result)->toBeTrue();
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
        '*/posts*' => Http::response([
            'posts' => [
                ['id' => 101, 'content' => 'Hello world!', 'status' => 'published', 'account_ids' => [1, 2], 'created_at' => '2025-01-15 10:00:00'],
                ['id' => 102, 'content' => 'Scheduled post', 'status' => 'scheduled', 'account_ids' => [1], 'created_at' => '2025-01-15 09:00:00'],
            ],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 2],
        ]),
    ]);

    $response = $this->client->posts()->paginate();

    expect($response)->toBeInstanceOf(PaginatedResponse::class);
    expect($response->items)->toHaveCount(2);
    expect($response->items[0])->toBeInstanceOf(Post::class);
});

test('all returns all posts', function () {
    Http::fake([
        '*/posts*' => Http::response($this->fixture('posts.json')),
    ]);

    $posts = $this->client->posts()->all();

    expect($posts)->toBeArray();
    expect($posts)->toHaveCount(2);
    expect($posts[0])->toBeInstanceOf(Post::class);
});

test('list handles items key from spec', function () {
    Http::fake([
        '*/posts*' => Http::response([
            'items' => [
                ['id' => 101, 'content' => 'Hello', 'status' => 'published', 'account_ids' => [1], 'created_at' => '2025-01-15 10:00:00'],
            ],
            'currentPage' => 1,
            'lastPage' => 1,
            'total' => 1,
        ]),
    ]);

    $posts = $this->client->posts()->list();

    expect($posts)->toHaveCount(1);
    expect($posts[0]->id)->toBe(101);
});

test('create sends options in payload', function () {
    Http::fake([
        '*/posts*' => Http::response([
            'success' => true,
            'posts' => [
                [
                    'id' => 300,
                    'content' => 'Reddit post',
                    'status' => 'published',
                    'account_ids' => [5],
                    'created_at' => '2025-01-15 10:00:00',
                ],
            ],
        ]),
    ]);

    $this->client->posts()->create(
        content: 'Reddit post',
        accountIds: [5],
        options: ['title' => 'My Reddit Title', 'subreddit' => 'laravel'],
    );

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['options']['title'] === 'My Reddit Title'
            && $request['options']['subreddit'] === 'laravel';
    });
});

test('create works without options', function () {
    Http::fake([
        '*/posts*' => Http::response([
            'success' => true,
            'posts' => [
                [
                    'id' => 301,
                    'content' => 'Simple post',
                    'status' => 'published',
                    'account_ids' => [1],
                    'created_at' => '2025-01-15 10:00:00',
                ],
            ],
        ]),
    ]);

    $this->client->posts()->create(
        content: 'Simple post',
        accountIds: [1],
    );

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && ! array_key_exists('options', $request->data());
    });
});

test('all fetches all pages', function () {
    Http::fake([
        '*/posts*' => Http::sequence()
            ->push([
                'posts' => [
                    ['id' => 1, 'content' => 'Post 1', 'status' => 'published', 'account_ids' => [1], 'created_at' => '2025-01-15 10:00:00'],
                ],
                'pagination' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
            ])
            ->push([
                'posts' => [
                    ['id' => 2, 'content' => 'Post 2', 'status' => 'published', 'account_ids' => [1], 'created_at' => '2025-01-15 10:00:00'],
                ],
                'pagination' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
            ]),
    ]);

    $posts = $this->client->posts()->all(perPage: 1);

    expect($posts)->toHaveCount(2);
    expect($posts[0]->id)->toBe(1);
    expect($posts[1]->id)->toBe(2);
});

test('lazy yields posts one at a time', function () {
    Http::fake([
        '*/posts*' => Http::response([
            'posts' => [
                ['id' => 101, 'content' => 'Hello world!', 'status' => 'published', 'account_ids' => [1, 2], 'created_at' => '2025-01-15 10:00:00'],
                ['id' => 102, 'content' => 'Scheduled post', 'status' => 'scheduled', 'account_ids' => [1], 'created_at' => '2025-01-15 09:00:00'],
            ],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 2],
        ]),
    ]);

    $posts = [];
    foreach ($this->client->posts()->lazy() as $post) {
        $posts[] = $post;
    }

    expect($posts)->toHaveCount(2);
});
