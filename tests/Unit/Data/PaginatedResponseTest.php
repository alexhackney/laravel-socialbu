<?php

declare(strict_types=1);

use Hei\SocialBu\Data\PaginatedResponse;

test('it creates paginated response from array', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ],
        'pagination' => [
            'current_page' => 1,
            'last_page' => 5,
            'per_page' => 15,
            'total' => 75,
        ],
    ]);

    expect($response->items)->toHaveCount(2);
    expect($response->currentPage)->toBe(1);
    expect($response->lastPage)->toBe(5);
    expect($response->perPage)->toBe(15);
    expect($response->total)->toBe(75);
});

test('it handles meta key for pagination', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [['id' => 1]],
        'meta' => [
            'current_page' => 2,
            'last_page' => 10,
            'per_page' => 20,
            'total' => 200,
        ],
    ]);

    expect($response->currentPage)->toBe(2);
    expect($response->lastPage)->toBe(10);
});

test('it handles camelCase field names', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => [
            'currentPage' => 3,
            'lastPage' => 8,
            'perPage' => 25,
        ],
    ]);

    expect($response->currentPage)->toBe(3);
    expect($response->lastPage)->toBe(8);
    expect($response->perPage)->toBe(25);
});

test('hasMorePages returns true when not on last page', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => ['current_page' => 1, 'last_page' => 5],
    ]);

    expect($response->hasMorePages())->toBeTrue();
});

test('hasMorePages returns false on last page', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => ['current_page' => 5, 'last_page' => 5],
    ]);

    expect($response->hasMorePages())->toBeFalse();
});

test('nextPage returns next page number when available', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => ['current_page' => 3, 'last_page' => 5],
    ]);

    expect($response->nextPage())->toBe(4);
});

test('nextPage returns null on last page', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => ['current_page' => 5, 'last_page' => 5],
    ]);

    expect($response->nextPage())->toBeNull();
});

test('previousPage returns previous page number when available', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => ['current_page' => 3, 'last_page' => 5],
    ]);

    expect($response->previousPage())->toBe(2);
});

test('previousPage returns null on first page', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => ['current_page' => 1, 'last_page' => 5],
    ]);

    expect($response->previousPage())->toBeNull();
});

test('isFirstPage and isLastPage work correctly', function () {
    $first = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => ['current_page' => 1, 'last_page' => 5],
    ]);

    $middle = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => ['current_page' => 3, 'last_page' => 5],
    ]);

    $last = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => ['current_page' => 5, 'last_page' => 5],
    ]);

    expect($first->isFirstPage())->toBeTrue();
    expect($first->isLastPage())->toBeFalse();
    expect($middle->isFirstPage())->toBeFalse();
    expect($middle->isLastPage())->toBeFalse();
    expect($last->isFirstPage())->toBeFalse();
    expect($last->isLastPage())->toBeTrue();
});

test('isEmpty returns true when no items', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [],
        'pagination' => ['current_page' => 1, 'last_page' => 1],
    ]);

    expect($response->isEmpty())->toBeTrue();
    expect($response->count())->toBe(0);
});

test('isEmpty returns false when items exist', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [['id' => 1]],
        'pagination' => ['current_page' => 1, 'last_page' => 1],
    ]);

    expect($response->isEmpty())->toBeFalse();
    expect($response->count())->toBe(1);
});

test('collect returns items as collection', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [['id' => 1], ['id' => 2]],
    ]);

    $collection = $response->collect();

    expect($collection)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($collection)->toHaveCount(2);
});

test('map transforms items through callback', function () {
    $response = PaginatedResponse::fromArray([
        'data' => [['id' => 1], ['id' => 2], ['id' => 3]],
    ]);

    $ids = $response->map(fn ($item) => $item['id']);

    expect($ids)->toBe([1, 2, 3]);
});

test('it uses custom items key', function () {
    $response = PaginatedResponse::fromArray([
        'posts' => [['id' => 1], ['id' => 2]],
        'pagination' => ['current_page' => 1, 'last_page' => 1],
    ], 'posts');

    expect($response->items)->toHaveCount(2);
});

test('it handles top-level camelCase pagination from spec', function () {
    // This is the actual format the SocialBu API spec describes:
    // items, currentPage, lastPage, nextPage, total at top level
    $response = PaginatedResponse::fromArray([
        'items' => [['id' => 1], ['id' => 2], ['id' => 3]],
        'currentPage' => 2,
        'lastPage' => 5,
        'total' => 25,
    ]);

    expect($response->items)->toHaveCount(3);
    expect($response->currentPage)->toBe(2);
    expect($response->lastPage)->toBe(5);
    expect($response->total)->toBe(25);
    expect($response->hasMorePages())->toBeTrue();
});

test('it falls back to items key when custom key is missing', function () {
    $response = PaginatedResponse::fromArray([
        'items' => [['id' => 1]],
        'currentPage' => 1,
        'lastPage' => 1,
        'total' => 1,
    ], 'posts'); // 'posts' key doesn't exist, should fall back to 'items'

    expect($response->items)->toHaveCount(1);
});
