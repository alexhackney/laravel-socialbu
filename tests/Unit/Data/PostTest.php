<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Hei\SocialBu\Data\Post;

test('it creates post from array', function () {
    $post = Post::fromArray([
        'id' => 123,
        'content' => 'Hello world',
        'status' => 'published',
        'account_ids' => [1, 2, 3],
        'publish_at' => '2025-06-15 14:00:00',
        'attachments' => [['url' => 'https://example.com/image.jpg']],
        'created_at' => '2025-01-01 12:00:00',
        'updated_at' => '2025-01-02 12:00:00',
    ]);

    expect($post->id)->toBe(123);
    expect($post->content)->toBe('Hello world');
    expect($post->status)->toBe('published');
    expect($post->accountIds)->toBe([1, 2, 3]);
    expect($post->publishAt)->toBeInstanceOf(CarbonImmutable::class);
    expect($post->publishAt->toDateTimeString())->toBe('2025-06-15 14:00:00');
    expect($post->attachments)->toBe([['url' => 'https://example.com/image.jpg']]);
    expect($post->createdAt->toDateTimeString())->toBe('2025-01-01 12:00:00');
    expect($post->updatedAt->toDateTimeString())->toBe('2025-01-02 12:00:00');
});

test('it parses accounts array with nested objects', function () {
    $post = Post::fromArray([
        'id' => 1,
        'accounts' => [
            ['id' => 10, 'name' => 'Account 1'],
            ['id' => 20, 'name' => 'Account 2'],
        ],
        'created_at' => '2025-01-01 12:00:00',
    ]);

    expect($post->accountIds)->toBe([10, 20]);
});

test('it converts to array', function () {
    $post = Post::fromArray([
        'id' => 123,
        'content' => 'Hello',
        'status' => 'draft',
        'account_ids' => [1],
        'created_at' => '2025-01-01 12:00:00',
    ]);

    $array = $post->toArray();

    expect($array['id'])->toBe(123);
    expect($array['content'])->toBe('Hello');
    expect($array['status'])->toBe('draft');
    expect($array['account_ids'])->toBe([1]);
    expect($array)->not->toHaveKey('publish_at');
});

test('isScheduled returns true for future publish date', function () {
    $post = Post::fromArray([
        'id' => 1,
        'publish_at' => CarbonImmutable::now()->addDay()->toDateTimeString(),
        'created_at' => '2025-01-01 12:00:00',
    ]);

    expect($post->isScheduled())->toBeTrue();
});

test('isScheduled returns false for past publish date', function () {
    $post = Post::fromArray([
        'id' => 1,
        'publish_at' => CarbonImmutable::now()->subDay()->toDateTimeString(),
        'created_at' => '2025-01-01 12:00:00',
    ]);

    expect($post->isScheduled())->toBeFalse();
});

test('isPublished returns true when status is published', function () {
    $post = Post::fromArray([
        'id' => 1,
        'status' => 'published',
        'created_at' => '2025-01-01 12:00:00',
    ]);

    expect($post->isPublished())->toBeTrue();
    expect($post->isDraft())->toBeFalse();
});

test('isDraft returns true when status is draft', function () {
    $post = Post::fromArray([
        'id' => 1,
        'status' => 'draft',
        'created_at' => '2025-01-01 12:00:00',
    ]);

    expect($post->isDraft())->toBeTrue();
    expect($post->isPublished())->toBeFalse();
});

test('it handles missing optional fields gracefully', function () {
    $post = Post::fromArray([
        'id' => 1,
    ]);

    expect($post->content)->toBe('');
    expect($post->status)->toBe('draft');
    expect($post->accountIds)->toBe([]);
    expect($post->publishAt)->toBeNull();
    expect($post->attachments)->toBeNull();
    expect($post->updatedAt)->toBeNull();
});
