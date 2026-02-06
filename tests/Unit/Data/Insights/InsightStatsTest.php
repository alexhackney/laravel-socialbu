<?php

declare(strict_types=1);

use Hei\SocialBu\Data\Insights\InsightStats;

test('it creates from camelCase array', function () {
    $stats = InsightStats::fromArray([
        'unreadFeeds' => 5,
        'userAutomations' => 2,
        'userPendingPosts' => 3,
        'userFailedPosts' => 1,
        'inactiveAccounts' => 0,
    ]);

    expect($stats->unreadFeeds)->toBe(5);
    expect($stats->userAutomations)->toBe(2);
    expect($stats->userPendingPosts)->toBe(3);
    expect($stats->userFailedPosts)->toBe(1);
    expect($stats->inactiveAccounts)->toBe(0);
});

test('it creates from snake_case array', function () {
    $stats = InsightStats::fromArray([
        'unread_feeds' => 10,
        'user_automations' => 4,
        'user_pending_posts' => 6,
        'user_failed_posts' => 2,
        'inactive_accounts' => 1,
    ]);

    expect($stats->unreadFeeds)->toBe(10);
    expect($stats->userAutomations)->toBe(4);
    expect($stats->userPendingPosts)->toBe(6);
    expect($stats->userFailedPosts)->toBe(2);
    expect($stats->inactiveAccounts)->toBe(1);
});

test('it handles missing fields with defaults', function () {
    $stats = InsightStats::fromArray([]);

    expect($stats->unreadFeeds)->toBe(0);
    expect($stats->userAutomations)->toBe(0);
    expect($stats->userPendingPosts)->toBe(0);
    expect($stats->userFailedPosts)->toBe(0);
    expect($stats->inactiveAccounts)->toBe(0);
});

test('it converts to array', function () {
    $stats = InsightStats::fromArray([
        'unreadFeeds' => 5,
        'userAutomations' => 2,
        'userPendingPosts' => 3,
        'userFailedPosts' => 1,
        'inactiveAccounts' => 0,
    ]);

    expect($stats->toArray())->toBe([
        'unreadFeeds' => 5,
        'userAutomations' => 2,
        'userPendingPosts' => 3,
        'userFailedPosts' => 1,
        'inactiveAccounts' => 0,
    ]);
});

test('hasIssues returns true when failed posts exist', function () {
    $stats = InsightStats::fromArray([
        'userFailedPosts' => 3,
        'inactiveAccounts' => 0,
    ]);

    expect($stats->hasIssues())->toBeTrue();
});

test('hasIssues returns true when inactive accounts exist', function () {
    $stats = InsightStats::fromArray([
        'userFailedPosts' => 0,
        'inactiveAccounts' => 2,
    ]);

    expect($stats->hasIssues())->toBeTrue();
});

test('hasIssues returns false when no issues', function () {
    $stats = InsightStats::fromArray([
        'userFailedPosts' => 0,
        'inactiveAccounts' => 0,
    ]);

    expect($stats->hasIssues())->toBeFalse();
});
