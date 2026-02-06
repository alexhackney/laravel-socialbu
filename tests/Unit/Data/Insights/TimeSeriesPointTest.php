<?php

declare(strict_types=1);

use Hei\SocialBu\Data\Insights\TimeSeriesPoint;

test('it creates from array with value key', function () {
    $point = TimeSeriesPoint::fromArray([
        'date' => '2026-01-15',
        'value' => 42,
    ]);

    expect($point->date)->toBe('2026-01-15');
    expect($point->value)->toBe(42);
});

test('it creates from array with count key', function () {
    $point = TimeSeriesPoint::fromArray([
        'date' => '2026-01-15',
        'count' => 7,
    ]);

    expect($point->date)->toBe('2026-01-15');
    expect($point->value)->toBe(7);
});

test('value key takes precedence over count key', function () {
    $point = TimeSeriesPoint::fromArray([
        'date' => '2026-01-15',
        'value' => 10,
        'count' => 5,
    ]);

    expect($point->value)->toBe(10);
});

test('it handles missing fields with defaults', function () {
    $point = TimeSeriesPoint::fromArray([]);

    expect($point->date)->toBe('');
    expect($point->value)->toBe(0);
});

test('it handles float values', function () {
    $point = TimeSeriesPoint::fromArray([
        'date' => '2026-01-15',
        'value' => 3.14,
    ]);

    expect($point->value)->toBe(3.14);
});

test('it converts to array', function () {
    $point = TimeSeriesPoint::fromArray([
        'date' => '2026-01-15',
        'value' => 42,
    ]);

    $array = $point->toArray();

    expect($array)->toBe([
        'date' => '2026-01-15',
        'value' => 42,
    ]);
});
