<?php

declare(strict_types=1);

use Hei\SocialBu\Client\SocialBuClient;
use Hei\SocialBu\Data\Insights\AccountMetrics;
use Hei\SocialBu\Data\Insights\InsightStats;
use Hei\SocialBu\Data\Insights\TimeSeriesPoint;
use Hei\SocialBu\Data\Insights\TopPost;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new SocialBuClient(
        token: 'test-token',
        accountIds: [83462],
    );
});

test('stats returns InsightStats', function () {
    Http::fake([
        '*/insights/stats*' => Http::response($this->fixture('insights-stats.json')),
    ]);

    $stats = $this->client->insights()->stats();

    expect($stats)->toBeInstanceOf(InsightStats::class);
    expect($stats->unreadFeeds)->toBe(5);
    expect($stats->userAutomations)->toBe(2);
    expect($stats->userPendingPosts)->toBe(3);
    expect($stats->userFailedPosts)->toBe(1);
    expect($stats->inactiveAccounts)->toBe(0);
});

test('postCounts returns array of TimeSeriesPoint', function () {
    Http::fake([
        '*/insights/posts/counts*' => Http::response($this->fixture('insights-post-counts.json')),
    ]);

    $counts = $this->client->insights()->postCounts('2026-01-01', '2026-02-06');

    expect($counts)->toBeArray();
    expect($counts)->toHaveCount(4);
    expect($counts[0])->toBeInstanceOf(TimeSeriesPoint::class);
    expect($counts[0]->date)->toBe('2026-01-01');
    expect($counts[0]->value)->toBe(3);
    expect($counts[1]->value)->toBe(5);
});

test('postCounts sends correct query parameters', function () {
    Http::fake([
        '*/insights/posts/counts*' => Http::response($this->fixture('insights-post-counts.json')),
    ]);

    $this->client->insights()->postCounts(
        start: '2026-01-01',
        end: '2026-02-06',
        accounts: [83462],
        postType: 'image',
    );

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'start=2026-01-01')
            && str_contains($request->url(), 'end=2026-02-06')
            && str_contains($request->url(), 'post_type=image');
    });
});

test('postCounts accepts DateTimeInterface for dates', function () {
    Http::fake([
        '*/insights/posts/counts*' => Http::response($this->fixture('insights-post-counts.json')),
    ]);

    $start = new DateTimeImmutable('2026-01-01');
    $end = new DateTimeImmutable('2026-02-06');

    $this->client->insights()->postCounts($start, $end);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'start=2026-01-01')
            && str_contains($request->url(), 'end=2026-02-06');
    });
});

test('postMetrics returns keyed array of TimeSeriesPoint arrays', function () {
    Http::fake([
        '*/insights/posts/metrics*' => Http::response($this->fixture('insights-post-metrics.json')),
    ]);

    $metrics = $this->client->insights()->postMetrics(
        '2026-01-01',
        '2026-02-06',
        ['likes', 'comments'],
        postType: 'image',
    );

    expect($metrics)->toBeArray();
    expect($metrics)->toHaveCount(2);
    expect($metrics)->toHaveKeys(['likes', 'comments']);
    expect($metrics['likes'])->toHaveCount(2);
    expect($metrics['likes'][0])->toBeInstanceOf(TimeSeriesPoint::class);
    expect($metrics['likes'][0]->date)->toBe('2026-01-01');
    expect($metrics['likes'][0]->value)->toBe(10);
    expect($metrics['comments'][1]->value)->toBe(8);
});

test('postMetrics returns empty array when no data', function () {
    Http::fake([
        '*/insights/posts/metrics*' => Http::response([
            'success' => true,
            'data' => [],
        ]),
    ]);

    $metrics = $this->client->insights()->postMetrics(
        '2026-01-01',
        '2026-02-06',
        ['likes'],
        postType: 'image',
    );

    expect($metrics)->toBe([]);
});

test('postMetrics sends metrics as comma-separated string', function () {
    Http::fake([
        '*/insights/posts/metrics*' => Http::response($this->fixture('insights-post-metrics.json')),
    ]);

    $this->client->insights()->postMetrics(
        '2026-01-01',
        '2026-02-06',
        ['likes', 'comments'],
        postType: 'image',
    );

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'metrics=likes%2Ccomments')
            || str_contains($request->url(), 'metrics=likes,comments');
    });
});

test('topPosts returns array of TopPost', function () {
    Http::fake([
        '*/insights/posts/top_posts*' => Http::response($this->fixture('insights-top-posts.json')),
    ]);

    $posts = $this->client->insights()->topPosts(
        '2025-06-01',
        '2025-06-30',
        ['likes'],
    );

    expect($posts)->toBeArray();
    expect($posts)->toHaveCount(2);
    expect($posts[0])->toBeInstanceOf(TopPost::class);
    expect($posts[0]->id)->toBe(501);
    expect($posts[0]->content)->toBe('Top performing post!');
    expect($posts[0]->accountId)->toBe(83462);
    expect($posts[0]->accountType)->toBe('instagram');
    expect($posts[0]->published)->toBeTrue();
    expect($posts[0]->insights)->toHaveCount(2);
    expect($posts[0]->insightValue('likes'))->toBe(142);
});

test('topPosts sends correct query parameters', function () {
    Http::fake([
        '*/insights/posts/top_posts*' => Http::response($this->fixture('insights-top-posts.json')),
    ]);

    $this->client->insights()->topPosts(
        '2025-06-01',
        '2025-06-30',
        ['likes', 'comments'],
        accounts: [83462],
    );

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'start=2025-06-01')
            && str_contains($request->url(), 'end=2025-06-30')
            && (str_contains($request->url(), 'metrics=likes%2Ccomments')
                || str_contains($request->url(), 'metrics=likes,comments'));
    });
});

test('accountMetrics returns array of AccountMetrics', function () {
    Http::fake([
        '*/insights/accounts/metrics*' => Http::response($this->fixture('insights-account-metrics.json')),
    ]);

    $metrics = $this->client->insights()->accountMetrics(
        '2025-01-01',
        '2026-02-06',
        ['followers'],
        accounts: [83462],
    );

    expect($metrics)->toBeArray();
    expect($metrics)->toHaveCount(1);
    expect($metrics[0])->toBeInstanceOf(AccountMetrics::class);
    expect($metrics[0]->accountId)->toBe(83462);
    expect($metrics[0]->metrics)->toHaveCount(2);
    expect($metrics[0]->metric('followers'))->toHaveCount(2);
    expect($metrics[0]->metric('followers')[0]->value)->toBe(1000);
});

test('accountMetrics sends calculate_growth parameter', function () {
    Http::fake([
        '*/insights/accounts/metrics*' => Http::response($this->fixture('insights-account-metrics.json')),
    ]);

    $this->client->insights()->accountMetrics(
        '2025-01-01',
        '2026-02-06',
        ['followers'],
        calculateGrowth: false,
    );

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'calculate_growth=');
    });
});
