<?php

declare(strict_types=1);

namespace Hei\SocialBu\Resources;

use Generator;
use Hei\SocialBu\Client\SocialBuClientInterface;
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
     * @param  string|null  $type  Filter by type: 'scheduled', 'published', 'draft', 'failed'
     * @return array<Post>
     */
    public function list(?string $type = null, int $page = 1, int $perPage = 15): array
    {
        $query = array_filter([
            'type' => $type,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $response = $this->client->get('/posts', $query);

        $items = $response['data'] ?? $response['posts'] ?? [];

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

        $data = $response['data'] ?? $response['post'] ?? $response;

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
    ): Post {
        $data = array_filter([
            'content' => $content,
            'accounts' => $accountIds,
            'publish_at' => $publishAt,
            'existing_attachments' => $attachments,
            'draft' => $draft ?: null,
            'postback_url' => $postbackUrl,
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
     * List posts with pagination info.
     */
    public function paginate(?string $type = null, int $page = 1, int $perPage = 15): PaginatedResponse
    {
        $query = array_filter([
            'type' => $type,
            'page' => $page,
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
        return $this->list($type, 1, $perPage);
    }
}
