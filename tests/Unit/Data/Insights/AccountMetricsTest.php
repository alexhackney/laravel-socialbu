<?php

declare(strict_types=1);

use Hei\SocialBu\Data\Insights\AccountMetrics;
use Hei\SocialBu\Data\Insights\TimeSeriesPoint;

test('it creates from array', function () {
    $metrics = AccountMetrics::fromArray([
        'account_id' => 83462,
        'metrics' => [
            'followers' => [
                ['date' => '2025-01-01', 'value' => 1000],
                ['date' => '2025-02-01', 'value' => 1050],
            ],
            'total_views' => [
                ['date' => '2025-01-01', 'value' => 5000],
                ['date' => '2025-02-01', 'value' => 6200],
            ],
        ],
    ]);

    expect($metrics->accountId)->toBe(83462);
    expect($metrics->metrics)->toHaveCount(2);
    expect($metrics->metrics['followers'])->toHaveCount(2);
    expect($metrics->metrics['followers'][0])->toBeInstanceOf(TimeSeriesPoint::class);
    expect($metrics->metrics['followers'][0]->date)->toBe('2025-01-01');
    expect($metrics->metrics['followers'][0]->value)->toBe(1000);
    expect($metrics->metrics['total_views'][1]->value)->toBe(6200);
});

test('it handles missing metrics', function () {
    $metrics = AccountMetrics::fromArray([
        'account_id' => 1,
    ]);

    expect($metrics->accountId)->toBe(1);
    expect($metrics->metrics)->toBe([]);
});

test('it converts to array', function () {
    $metrics = AccountMetrics::fromArray([
        'account_id' => 83462,
        'metrics' => [
            'followers' => [
                ['date' => '2025-01-01', 'value' => 1000],
            ],
        ],
    ]);

    $array = $metrics->toArray();

    expect($array['account_id'])->toBe(83462);
    expect($array['metrics']['followers'])->toBe([
        ['date' => '2025-01-01', 'value' => 1000],
    ]);
});

test('metric helper returns time series for existing metric', function () {
    $metrics = AccountMetrics::fromArray([
        'account_id' => 1,
        'metrics' => [
            'followers' => [
                ['date' => '2025-01-01', 'value' => 1000],
            ],
        ],
    ]);

    $followers = $metrics->metric('followers');

    expect($followers)->toHaveCount(1);
    expect($followers[0])->toBeInstanceOf(TimeSeriesPoint::class);
    expect($followers[0]->value)->toBe(1000);
});

test('metric helper returns null for missing metric', function () {
    $metrics = AccountMetrics::fromArray([
        'account_id' => 1,
        'metrics' => [
            'followers' => [
                ['date' => '2025-01-01', 'value' => 1000],
            ],
        ],
    ]);

    expect($metrics->metric('impressions'))->toBeNull();
});
