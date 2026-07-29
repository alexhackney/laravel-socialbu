<?php

declare(strict_types=1);

namespace Hei\SocialBu\Resources;

use Generator;
use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\AccountSupportedOptions;
use Hei\SocialBu\Data\PaginatedResponse;
use Hei\SocialBu\Data\Post;

class PostResource
{
    public function __construct(
        private readonly SocialBuClientInterface $client,
    ) {}

    /**
     * List posts with optional filtering.
     *
     * @param  string|null  $type  Filter by type: 'scheduled', 'published', 'draft', 'awaiting_approval', 'scheduled_or_awaiting_approval'
     * @return array<Post>
     */
    public function list(?string $type = null, int $page = 1, int $perPage = 15): array
    {
        $query = array_filter([
            'type' => $type,
            'page' => $page,
            // The API documents the page size as camelCase `perPage`, matching
            // the camelCase pagination fields it returns. Older versions of the
            // spec documented no page-size parameter at all, so `per_page` is
            // sent alongside it until the live API confirms which it honours.
            'perPage' => $perPage,
            'per_page' => $perPage,
        ]);

        $response = $this->client->get('/posts', $query);

        $items = array_is_list($response)
            ? $response
            : $response['posts'] ?? $response['items'] ?? $response['data'] ?? [];

        return array_map(
            fn (array $data) => Post::fromArray($data),
            $items
        );
    }

    /**
     * Get a specific post by ID.
     */
    public function get(int $postId): Post
    {
        $response = $this->client->get("/posts/{$postId}");

        $data = $response['post'] ?? $response['data'] ?? $response;

        return Post::fromArray($data);
    }

    /**
     * Create a new post.
     *
     * The API returns one post per account. This method returns the first post.
     *
     * @param  array<int>  $accountIds
     * @param  array<array{upload_token: string}>|null  $attachments
     */
    public function create(
        string $content,
        array $accountIds,
        ?string $publishAt = null,
        ?array $attachments = null,
        bool $draft = false,
        ?string $postbackUrl = null,
        ?array $options = null,
    ): Post {
        $data = array_filter([
            'content' => $content,
            'accounts' => $accountIds,
            'publish_at' => $publishAt ?? now()->toDateTimeString(),
            'existing_attachments' => $attachments,
            'draft' => $draft ?: null,
            'postback_url' => $postbackUrl,
            'options' => $options,
        ], fn ($value) => $value !== null);

        $response = $this->client->post('/posts', $data);

        // API returns {"success": bool, "posts": [...]} with one post per account
        $posts = $response['posts'] ?? $response['data'] ?? [];

        if (is_array($posts) && ! empty($posts)) {
            return Post::fromArray($posts[0]);
        }

        // Fallback: try to parse the response directly as a post
        return Post::fromArray($response['post'] ?? $response);
    }

    /**
     * Update an existing post.
     *
     * The API returns {"success": bool, "message": string}, not the post object.
     */
    public function update(int $postId, array $data): bool
    {
        $response = $this->client->patch("/posts/{$postId}", $data);

        return $response['success'] ?? true;
    }

    /**
     * Delete a post.
     */
    public function delete(int $postId): bool
    {
        $this->client->delete("/posts/{$postId}");

        return true;
    }

    /**
     * Publish an existing scheduled or draft post immediately.
     *
     * Named to keep it distinct from SocialBu::publish(), which creates a new
     * post rather than releasing one that already exists.
     */
    public function publishNow(int $postId): Post
    {
        $response = $this->client->post("/posts/{$postId}/publish");

        $data = $response['post'] ?? $response['data'] ?? $response;

        // The API returns a partial post, so carry the id through in case the
        // payload omits it.
        return Post::fromArray(array_merge(['id' => $postId], is_array($data) ? $data : []));
    }

    /**
     * Delete several posts in one request.
     *
     * @param  array<int>  $postIds
     */
    public function bulkDelete(array $postIds): bool
    {
        if ($postIds === []) {
            return true;
        }

        $this->client->post('/posts/bulk_delete', [
            'ids' => array_values(array_map('intval', $postIds)),
        ]);

        return true;
    }

    /**
     * Get the publish options each account supports.
     *
     * Use this to discover what may go in a post's `options` payload -- which
     * keys exist, which are required, and what a dropdown will accept -- before
     * spending a request on a post the platform would reject.
     *
     * @param  array<int>|null  $accountIds  Defaults to every available account.
     * @return array<int, AccountSupportedOptions> Keyed by account ID.
     */
    public function supportedOptions(?array $accountIds = null): array
    {
        $query = [];

        if ($accountIds !== null && $accountIds !== []) {
            $query['account_ids'] = implode(',', array_map('intval', $accountIds));
        }

        $response = $this->client->get('/posts/supported-options', $query);

        $accounts = array_is_list($response)
            ? $response
            : $response['accounts'] ?? $response['items'] ?? $response['data'] ?? [];

        $result = [];

        foreach ($accounts as $account) {
            if (! is_array($account)) {
                continue;
            }

            $options = AccountSupportedOptions::fromArray($account);
            $result[$options->accountId] = $options;
        }

        return $result;
    }

    /**
     * List posts with pagination info.
     */
    public function paginate(?string $type = null, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = array_filter([
            'type' => $type,
            'page' => $page,
            // The API documents the page size as camelCase `perPage`, matching
            // the camelCase pagination fields it returns. Older versions of the
            // spec documented no page-size parameter at all, so `per_page` is
            // sent alongside it until the live API confirms which it honours.
            'perPage' => $perPage,
            'per_page' => $perPage,
        ]);

        $response = $this->client->get('/posts', $query);

        $paginated = PaginatedResponse::fromArray($response, 'posts');

        return new PaginatedResponse(
            items: array_map(
                fn (array $data) => Post::fromArray($data),
                $paginated->items
            ),
            currentPage: $paginated->currentPage,
            lastPage: $paginated->lastPage,
            perPage: $paginated->perPage,
            total: $paginated->total,
        );
    }

    /**
     * Lazily iterate through all posts.
     *
     * @return Generator<Post>
     */
    public function lazy(?string $type = null, int $perPage = 15): Generator
    {
        $page = 1;

        do {
            $response = $this->paginate($type, $page, $perPage);

            foreach ($response->items as $post) {
                yield $post;
            }

            $page++;
        } while ($response->hasMorePages());
    }

    /**
     * Get all posts at once.
     *
     * @return array<Post>
     */
    public function all(?string $type = null, int $perPage = 50): array
    {
        return iterator_to_array($this->lazy($type, $perPage));
    }
}
