<?php

declare(strict_types=1);

use Hei\SocialBu\Client\SocialBuClient;
use Hei\SocialBu\Data\AccountSupportedOptions;
use Hei\SocialBu\Data\PostOption;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new SocialBuClient(
        token: 'test-token',
        accountIds: [123, 456],
    );
});

test('supportedOptions returns options keyed by account id', function () {
    Http::fake([
        '*/posts/supported-options*' => Http::response($this->fixture('posts-supported-options.json')),
    ]);

    $options = $this->client->posts()->supportedOptions();

    expect($options)->toHaveCount(2);
    expect(array_keys($options))->toBe([123, 456]);
    expect($options[123])->toBeInstanceOf(AccountSupportedOptions::class);
    expect($options[123]->accountType)->toBe('youtube');
    expect($options[123]->accountName)->toBe('My Channel');
    expect($options[456]->accountType)->toBe('reddit');
});

test('supportedOptions sends account ids as a comma separated list', function () {
    Http::fake([
        '*/posts/supported-options*' => Http::response($this->fixture('posts-supported-options.json')),
    ]);

    $this->client->posts()->supportedOptions([123, 456]);

    Http::assertSent(function ($request) {
        return str_contains(urldecode($request->url()), 'account_ids=123,456');
    });
});

test('supportedOptions omits the account filter when none is given', function () {
    Http::fake([
        '*/posts/supported-options*' => Http::response($this->fixture('posts-supported-options.json')),
    ]);

    $this->client->posts()->supportedOptions();

    Http::assertSent(function ($request) {
        return ! str_contains($request->url(), 'account_ids');
    });
});

test('it parses option definitions into typed objects', function () {
    Http::fake([
        '*/posts/supported-options*' => Http::response($this->fixture('posts-supported-options.json')),
    ]);

    $youtube = $this->client->posts()->supportedOptions()[123];

    $title = $youtube->option('video_title');
    expect($title)->toBeInstanceOf(PostOption::class);
    expect($title->label)->toBe('Video Title');
    expect($title->type)->toBe(PostOption::TYPE_STRING);
    expect($title->required)->toBeTrue();
    expect($title->maxLength)->toBe(100);

    $privacy = $youtube->option('privacy_status');
    expect($privacy->isDropdown())->toBeTrue();
    expect($privacy->choiceValues())->toBe(['public', 'private', 'unlisted']);
    expect($privacy->defaultValue)->toBe('public');

    expect($youtube->option('made_for_kids')->isBoolean())->toBeTrue();
    expect($youtube->option('thumbnail')->isFile())->toBeTrue();
    expect($youtube->option('nope'))->toBeNull();
});

test('it reports which option keys an account supports', function () {
    Http::fake([
        '*/posts/supported-options*' => Http::response($this->fixture('posts-supported-options.json')),
    ]);

    $reddit = $this->client->posts()->supportedOptions()[456];

    expect($reddit->keys())->toBe(['title', 'is_nsfw']);
    expect($reddit->supports('title'))->toBeTrue();
    expect($reddit->supports('video_title'))->toBeFalse();
    expect(array_keys($reddit->requiredOptions()))->toBe(['title']);
});

test('validate accepts a payload that satisfies the definitions', function () {
    Http::fake([
        '*/posts/supported-options*' => Http::response($this->fixture('posts-supported-options.json')),
    ]);

    $youtube = $this->client->posts()->supportedOptions()[123];

    expect($youtube->validate([
        'video_title' => 'My video',
        'privacy_status' => 'unlisted',
    ]))->toBe([]);
});

test('validate rejects unsupported keys, bad choices, overlong strings, and missing required options', function () {
    Http::fake([
        '*/posts/supported-options*' => Http::response($this->fixture('posts-supported-options.json')),
    ]);

    $youtube = $this->client->posts()->supportedOptions()[123];

    $unsupported = $youtube->validate(['video_title' => 'ok', 'flair_id' => 7]);
    expect($unsupported)->toHaveKey('flair_id');
    expect($unsupported['flair_id'])->toContain('not supported');

    $badChoice = $youtube->validate(['video_title' => 'ok', 'privacy_status' => 'secret']);
    expect($badChoice['privacy_status'])->toContain('public, private, unlisted');

    $tooLong = $youtube->validate(['video_title' => str_repeat('a', 101)]);
    expect($tooLong['video_title'])->toContain('maximum length of 100');

    $missing = $youtube->validate(['privacy_status' => 'public']);
    expect($missing['video_title'])->toContain('required');
});

test('it handles a plain array response without the accounts wrapper', function () {
    // The live API has returned bare arrays for other list endpoints, so the
    // wrapper is treated as optional here too.
    Http::fake([
        '*/posts/supported-options*' => Http::response([
            [
                'account_id' => 789,
                'account_type' => 'bluesky',
                'account_name' => 'test.bsky.social',
                'options' => [],
            ],
        ]),
    ]);

    $options = $this->client->posts()->supportedOptions();

    expect($options)->toHaveCount(1);
    expect($options[789]->accountType)->toBe('bluesky');
    expect($options[789]->keys())->toBe([]);
});
