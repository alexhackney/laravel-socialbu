<?php

declare(strict_types=1);

use Hei\SocialBu\Data\Insights\PostInsight;

test('it creates from array', function () {
    $insight = PostInsight::fromArray([
        'type' => 'likes',
        'value' => 142,
    ]);

    expect($insight->type)->toBe('likes');
    expect($insight->value)->toBe(142);
});

test('it handles float values', function () {
    $insight = PostInsight::fromArray([
        'type' => 'engagement_rate',
        'value' => 4.5,
    ]);

    expect($insight->value)->toBe(4.5);
});

test('it handles missing fields with defaults', function () {
    $insight = PostInsight::fromArray([]);

    expect($insight->type)->toBe('');
    expect($insight->value)->toBe(0);
});

test('it converts to array', function () {
    $insight = PostInsight::fromArray([
        'type' => 'comments',
        'value' => 23,
    ]);

    expect($insight->toArray())->toBe([
        'type' => 'comments',
        'value' => 23,
    ]);
});
