<?php

declare(strict_types=1);

use Hei\SocialBu\Data\Insights\PostInsight;
use Hei\SocialBu\Data\Insights\TopPost;

test('it creates from full array', function () {
    $post = TopPost::fromArray([
        'id' => 501,
        'content' => 'Top performing post!',
        'account_id' => 83462,
        'account_type' => 'instagram',
        'type' => 'image',
        'attachments' => [['url' => 'https://example.com/image.jpg']],
        'publish_at' => null,
        'published_at' => '2025-06-15 10:00:00',
        'published' => true,
        'permalink' => 'https://instagram.com/p/abc123',
        'insights' => [
            ['type' => 'likes', 'value' => 142],
            ['type' => 'comments', 'value' => 23],
        ],
    ]);

    expect($post->id)->toBe(501);
    expect($post->content)->toBe('Top performing post!');
    expect($post->accountId)->toBe(83462);
    expect($post->accountType)->toBe('instagram');
    expect($post->type)->toBe('image');
    expect($post->attachments)->toBe([['url' => 'https://example.com/image.jpg']]);
    expect($post->publishAt)->toBeNull();
    expect($post->publishedAt)->toBe('2025-06-15 10:00:00');
    expect($post->published)->toBeTrue();
    expect($post->permalink)->toBe('https://instagram.com/p/abc123');
    expect($post->insights)->toHaveCount(2);
    expect($post->insights[0])->toBeInstanceOf(PostInsight::class);
    expect($post->insights[0]->type)->toBe('likes');
    expect($post->insights[0]->value)->toBe(142);
});

test('it creates from minimal array', function () {
    $post = TopPost::fromArray([
        'id' => 502,
        'content' => 'Minimal post',
        'account_id' => 1,
        'account_type' => 'facebook.page',
        'type' => 'text',
    ]);

    expect($post->id)->toBe(502);
    expect($post->content)->toBe('Minimal post');
    expect($post->attachments)->toBeNull();
    expect($post->publishAt)->toBeNull();
    expect($post->publishedAt)->toBeNull();
    expect($post->published)->toBeFalse();
    expect($post->permalink)->toBeNull();
    expect($post->insights)->toBe([]);
});

test('it converts to array', function () {
    $post = TopPost::fromArray([
        'id' => 501,
        'content' => 'Test post',
        'account_id' => 83462,
        'account_type' => 'instagram',
        'type' => 'image',
        'published_at' => '2025-06-15 10:00:00',
        'published' => true,
        'permalink' => 'https://instagram.com/p/abc123',
        'insights' => [
            ['type' => 'likes', 'value' => 100],
        ],
    ]);

    $array = $post->toArray();

    expect($array['id'])->toBe(501);
    expect($array['content'])->toBe('Test post');
    expect($array['account_id'])->toBe(83462);
    expect($array['account_type'])->toBe('instagram');
    expect($array['type'])->toBe('image');
    expect($array['published'])->toBeTrue();
    expect($array['permalink'])->toBe('https://instagram.com/p/abc123');
    expect($array['insights'])->toBe([['type' => 'likes', 'value' => 100]]);
    expect($array)->not->toHaveKey('attachments');
    expect($array)->not->toHaveKey('publish_at');
});

test('insightValue returns value for existing type', function () {
    $post = TopPost::fromArray([
        'id' => 501,
        'content' => 'Test',
        'account_id' => 1,
        'account_type' => 'instagram',
        'type' => 'image',
        'insights' => [
            ['type' => 'likes', 'value' => 142],
            ['type' => 'comments', 'value' => 23],
        ],
    ]);

    expect($post->insightValue('likes'))->toBe(142);
    expect($post->insightValue('comments'))->toBe(23);
});

test('insightValue returns null for missing type', function () {
    $post = TopPost::fromArray([
        'id' => 501,
        'content' => 'Test',
        'account_id' => 1,
        'account_type' => 'instagram',
        'type' => 'image',
        'insights' => [
            ['type' => 'likes', 'value' => 142],
        ],
    ]);

    expect($post->insightValue('shares'))->toBeNull();
});
