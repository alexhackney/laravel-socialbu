<?php

declare(strict_types=1);

use Hei\SocialBu\Client\SocialBuClient;
use Hei\SocialBu\Data\Account;
use Hei\SocialBu\Data\PaginatedResponse;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new SocialBuClient(
        token: 'test-token',
        accountIds: [123, 456],
    );
});

test('list returns array of accounts', function () {
    Http::fake([
        '*/accounts*' => Http::response($this->fixture('accounts.json')),
    ]);

    $accounts = $this->client->accounts()->list();

    expect($accounts)->toBeArray();
    expect($accounts)->toHaveCount(2);
    expect($accounts[0])->toBeInstanceOf(Account::class);
    expect($accounts[0]->id)->toBe(1);
    expect($accounts[0]->name)->toBe('My Facebook Page');
    expect($accounts[0]->type)->toBe('facebook');
});

test('list passes query parameters', function () {
    Http::fake([
        '*/accounts*' => Http::response($this->fixture('accounts.json')),
    ]);

    $this->client->accounts()->list(type: 'user', page: 2, perPage: 25);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'type=user')
            && str_contains($request->url(), 'page=2')
            && str_contains($request->url(), 'per_page=25');
    });
});

test('get returns single account', function () {
    Http::fake([
        '*/accounts/1*' => Http::response([
            'id' => 1,
            'name' => 'My Facebook Page',
            'type' => 'facebook',
            'status' => 'active',
        ]),
    ]);

    $account = $this->client->accounts()->get(1);

    expect($account)->toBeInstanceOf(Account::class);
    expect($account->id)->toBe(1);
    expect($account->name)->toBe('My Facebook Page');
});

test('paginate returns PaginatedResponse with accounts', function () {
    Http::fake([
        '*/accounts*' => Http::response([
            'accounts' => [
                ['id' => 1, 'name' => 'My Facebook Page', 'type' => 'facebook', 'status' => 'active'],
                ['id' => 2, 'name' => 'My Instagram', 'type' => 'instagram', 'status' => 'active'],
            ],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 2],
        ]),
    ]);

    $response = $this->client->accounts()->paginate();

    expect($response)->toBeInstanceOf(PaginatedResponse::class);
    expect($response->items)->toHaveCount(2);
    expect($response->items[0])->toBeInstanceOf(Account::class);
    expect($response->currentPage)->toBe(1);
    expect($response->lastPage)->toBe(1);
    expect($response->total)->toBe(2);
});

test('lazy yields accounts one at a time', function () {
    Http::fake([
        '*/accounts*' => Http::response([
            'accounts' => [
                ['id' => 1, 'name' => 'My Facebook Page', 'type' => 'facebook', 'status' => 'active'],
                ['id' => 2, 'name' => 'My Instagram', 'type' => 'instagram', 'status' => 'active'],
            ],
            'pagination' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 15, 'total' => 2],
        ]),
    ]);

    $accounts = [];
    foreach ($this->client->accounts()->lazy() as $account) {
        $accounts[] = $account;
    }

    expect($accounts)->toHaveCount(2);
    expect($accounts[0])->toBeInstanceOf(Account::class);
});

test('all returns all accounts', function () {
    Http::fake([
        '*/accounts*' => Http::response($this->fixture('accounts.json')),
    ]);

    $accounts = $this->client->accounts()->all();

    expect($accounts)->toBeArray();
    expect($accounts)->toHaveCount(2);
});

test('all fetches all pages', function () {
    Http::fake([
        '*/accounts*' => Http::sequence()
            ->push([
                'accounts' => [['id' => 1, 'name' => 'Account 1', 'type' => 'facebook', 'status' => 'active']],
                'pagination' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
            ])
            ->push([
                'accounts' => [['id' => 2, 'name' => 'Account 2', 'type' => 'instagram', 'status' => 'active']],
                'pagination' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
            ]),
    ]);

    $accounts = $this->client->accounts()->all(perPage: 1);

    expect($accounts)->toHaveCount(2);
    expect($accounts[0]->id)->toBe(1);
    expect($accounts[1]->id)->toBe(2);
});

test('list handles items key from spec', function () {
    Http::fake([
        '*/accounts*' => Http::response([
            'items' => [
                ['id' => 1, 'name' => 'Account 1', 'type' => 'facebook', 'status' => 'active'],
            ],
            'currentPage' => 1,
            'lastPage' => 1,
            'total' => 1,
        ]),
    ]);

    $accounts = $this->client->accounts()->list();

    expect($accounts)->toHaveCount(1);
    expect($accounts[0]->id)->toBe(1);
});

test('lazy paginates through multiple pages', function () {
    Http::fake([
        '*/accounts*' => Http::sequence()
            ->push([
                'accounts' => [['id' => 1, 'name' => 'Account 1', 'type' => 'facebook', 'status' => 'active']],
                'pagination' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
            ])
            ->push([
                'accounts' => [['id' => 2, 'name' => 'Account 2', 'type' => 'instagram', 'status' => 'active']],
                'pagination' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
            ]),
    ]);

    $accounts = iterator_to_array($this->client->accounts()->lazy(perPage: 1));

    expect($accounts)->toHaveCount(2);
    expect($accounts[0]->id)->toBe(1);
    expect($accounts[1]->id)->toBe(2);
});
