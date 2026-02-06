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

test('isActive maps active bool to status', function () {
    $active = Account::fromArray(['id' => 1, 'name' => 'Test', 'active' => true]);
    $inactive = Account::fromArray(['id' => 1, 'name' => 'Test', 'active' => false]);

    expect($active->isActive())->toBeTrue();
    expect($active->status)->toBe('active');
    expect($inactive->isActive())->toBeFalse();
    expect($inactive->status)->toBe('inactive');
});

test('it maps image field to avatarUrl', function () {
    $account = Account::fromArray([
        'id' => 1,
        'name' => 'Test',
        'image' => 'https://example.com/avatar.jpg',
    ]);

    expect($account->avatarUrl)->toBe('https://example.com/avatar.jpg');
});

test('platform type helpers work correctly', function () {
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'facebook'])->isFacebook())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'instagram'])->isInstagram())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'twitter'])->isTwitter())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'x'])->isTwitter())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'linkedin'])->isLinkedIn())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'tiktok'])->isTikTok())->toBeTrue();
});

test('platform type helpers handle dotted types from real API', function () {
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'twitter.profile'])->isTwitter())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'facebook.page'])->isFacebook())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'instagram.business'])->isInstagram())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'linkedin.page'])->isLinkedIn())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'tiktok.business'])->isTikTok())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'pinterest.board'])->isPinterest())->toBeTrue();
});

test('requiresMedia returns true for media-required platforms', function () {
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'instagram'])->requiresMedia())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'tiktok'])->requiresMedia())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'pinterest'])->requiresMedia())->toBeTrue();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'instagram.business'])->requiresMedia())->toBeTrue();
});

test('requiresMedia returns false for text-capable platforms', function () {
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'facebook'])->requiresMedia())->toBeFalse();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'twitter'])->requiresMedia())->toBeFalse();
    expect(Account::fromArray(['id' => 1, 'name' => 'T', 'type' => 'twitter.profile'])->requiresMedia())->toBeFalse();
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
    expect($account->postMaxLength)->toBeNull();
    expect($account->maxAttachments)->toBeNull();
    expect($account->attachmentTypes)->toBeNull();
    expect($account->postMediaRequired)->toBeNull();
});

test('it parses capability fields from API response', function () {
    $account = Account::fromArray([
        'id' => 1,
        'name' => 'My Instagram',
        'type' => 'instagram',
        'status' => 'active',
        'post_maxlength' => 2200,
        'max_attachments' => 10,
        'attachment_types' => ['jpg', 'png', 'mp4'],
        'post_media_required' => true,
    ]);

    expect($account->postMaxLength)->toBe(2200);
    expect($account->maxAttachments)->toBe(10);
    expect($account->attachmentTypes)->toBe(['jpg', 'png', 'mp4']);
    expect($account->postMediaRequired)->toBeTrue();
});

test('capability fields are included in toArray', function () {
    $account = Account::fromArray([
        'id' => 1,
        'name' => 'Test',
        'type' => 'instagram',
        'post_maxlength' => 2200,
        'max_attachments' => 10,
        'post_media_required' => true,
    ]);

    $array = $account->toArray();

    expect($array['post_maxlength'])->toBe(2200);
    expect($array['max_attachments'])->toBe(10);
    expect($array['post_media_required'])->toBeTrue();
});

test('requiresMedia uses postMediaRequired field when available', function () {
    // Facebook with postMediaRequired=true overrides type-based check
    $account = Account::fromArray([
        'id' => 1,
        'name' => 'Special Facebook',
        'type' => 'facebook',
        'post_media_required' => true,
    ]);

    expect($account->requiresMedia())->toBeTrue();

    // Instagram with postMediaRequired=false overrides type-based check
    $account = Account::fromArray([
        'id' => 2,
        'name' => 'Text Instagram',
        'type' => 'instagram',
        'post_media_required' => false,
    ]);

    expect($account->requiresMedia())->toBeFalse();
});

test('requiresMedia falls back to type check when field is null', function () {
    $instagram = Account::fromArray([
        'id' => 1,
        'name' => 'Instagram',
        'type' => 'instagram',
    ]);

    $facebook = Account::fromArray([
        'id' => 2,
        'name' => 'Facebook',
        'type' => 'facebook',
    ]);

    expect($instagram->postMediaRequired)->toBeNull();
    expect($instagram->requiresMedia())->toBeTrue();
    expect($facebook->postMediaRequired)->toBeNull();
    expect($facebook->requiresMedia())->toBeFalse();
});
