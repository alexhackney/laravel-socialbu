<?php

declare(strict_types=1);

use Hei\SocialBu\Data\Account;

test('it creates account from array', function () {
    $account = Account::fromArray([
        'id' => 123,
        'name' => 'My Facebook Page',
        'type' => 'facebook',
        'status' => 'active',
        'username' => '@mypage',
        'profile_url' => 'https://facebook.com/mypage',
        'avatar_url' => 'https://example.com/avatar.jpg',
        'extra_data' => ['page_id' => '12345'],
    ]);

    expect($account->id)->toBe(123);
    expect($account->name)->toBe('My Facebook Page');
    expect($account->type)->toBe('facebook');
    expect($account->status)->toBe('active');
    expect($account->username)->toBe('@mypage');
    expect($account->profileUrl)->toBe('https://facebook.com/mypage');
    expect($account->avatarUrl)->toBe('https://example.com/avatar.jpg');
    expect($account->extraData)->toBe(['page_id' => '12345']);
});

test('it handles platform field as type alias', function () {
    $account = Account::fromArray([
        'id' => 1,
        'name' => 'Test',
        'platform' => 'instagram',
    ]);

    expect($account->type)->toBe('instagram');
});

test('it converts to array', function () {
    $account = Account::fromArray([
        'id' => 1,
        'name' => 'Test',
        'type' => 'twitter',
        'status' => 'active',
    ]);

    $array = $account->toArray();

    expect($array['id'])->toBe(1);
    expect($array['name'])->toBe('Test');
    expect($array['type'])->toBe('twitter');
    expect($array['status'])->toBe('active');
    expect($array)->not->toHaveKey('username');
});

test('isActive returns true when status is active', function () {
    $account = Account::fromArray([
        'id' => 1,
        'name' => 'Test',
        'status' => 'active',
    ]);

    expect($account->isActive())->toBeTrue();
});

test('isActive returns false when status is not active', function () {
    $account = Account::fromArray([
        'id' => 1,
        'name' => 'Test',
        'status' => 'disconnected',
    ]);

    expect($account->isActive())->toBeFalse();
});

test('platform type helpers work correctly', function () {
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'facebook'])->isFacebook())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'instagram'])->isInstagram())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'twitter'])->isTwitter())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'x'])->isTwitter())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'linkedin'])->isLinkedIn())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'tiktok'])->isTikTok())->toBeTrue();
});

test('requiresMedia returns true for media-required platforms', function () {
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'instagram'])->requiresMedia())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'tiktok'])->requiresMedia())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'pinterest'])->requiresMedia())->toBeTrue();
});

test('requiresMedia returns false for text-capable platforms', function () {
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'facebook'])->requiresMedia())->toBeFalse();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'twitter'])->requiresMedia())->toBeFalse();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'linkedin'])->requiresMedia())->toBeFalse();
});

test('it handles missing optional fields gracefully', function () {
    $account = Account::fromArray([
        'id' => 1,
        'name' => 'Test',
    ]);

    expect($account->type)->toBe('unknown');
    expect($account->status)->toBe('active');
    expect($account->username)->toBeNull();
    expect($account->profileUrl)->toBeNull();
    expect($account->avatarUrl)->toBeNull();
    expect($account->extraData)->toBeNull();
});
