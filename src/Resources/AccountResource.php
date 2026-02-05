<?php

declare(strict_types=1);

namespace Hei\SocialBu\Resources;

use Generator;
use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\Account;
use Hei\SocialBu\Data\PaginatedResponse;

class AccountResource
{
    public function __construct(
        private readonly SocialBuClientInterface $client,
    ) {}

    /**
     * List all accounts.
     *
     * @return array<Account>
     */
    public function list(?string $type = null, int $page = 1, int $perPage = 15): array
    {
        $query = array_filter([
            'type' => $type,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $response = $this->client->get('/accounts', $query);

        $items = $response['accounts'] ?? $response['items'] ?? $response['data'] ?? [];

        return array_map(
            fn (array $data) => Account::fromArray($data),
            $items
        );
    }

    /**
     * Get a specific account by ID.
     */
    public function get(int $accountId): Account
    {
        $response = $this->client->get("/accounts/{$accountId}");

        $data = $response['account'] ?? $response['data'] ?? $response;

        return Account::fromArray($data);
    }

    /**
     * List accounts with pagination info.
     */
    public function paginate(?string $type = null, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = array_filter([
            'type' => $type,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $response = $this->client->get('/accounts', $query);

        $paginated = PaginatedResponse::fromArray($response, 'accounts');

        return new PaginatedResponse(
            items: array_map(
                fn (array $data) => Account::fromArray($data),
                $paginated->items
            ),
            currentPage: $paginated->currentPage,
            lastPage: $paginated->lastPage,
            perPage: $paginated->perPage,
            total: $paginated->total,
        );
    }

    /**
     * Lazily iterate through all accounts.
     *
     * @return Generator<Account>
     */
    public function lazy(?string $type = null, int $perPage = 15): Generator
    {
        $page = 1;

        do {
            $response = $this->paginate($type, $page, $perPage);

            foreach ($response->items as $account) {
                yield $account;
            }

            $page++;
        } while ($response->hasMorePages());
    }

    /**
     * Get all accounts at once.
     *
     * @return array<Account>
     */
    public function all(?string $type = null, int $perPage = 50): array
    {
        return $this->list($type, 1, $perPage);
    }
}
