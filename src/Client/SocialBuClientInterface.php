<?php

declare(strict_types=1);

namespace Hei\SocialBu\Client;

use Hei\SocialBu\Builders\PostBuilder;
use Hei\SocialBu\Data\Post;
use Hei\SocialBu\Resources\AccountResource;
use Hei\SocialBu\Resources\InsightsResource;
use Hei\SocialBu\Resources\MediaResource;
use Hei\SocialBu\Resources\PostResource;

/**
 * The raw request methods return the decoded JSON body as-is. SocialBu replies
 * with an object on most endpoints but with a top-level list on others (e.g.
 * GET /accounts), so the array is keyed by `array-key` rather than `string`.
 */
interface SocialBuClientInterface
{
    /**
     * Get the posts resource.
     */
    public function posts(): PostResource;

    /**
     * Get the accounts resource.
     */
    public function accounts(): AccountResource;

    /**
     * Get the media resource.
     */
    public function media(): MediaResource;

    /**
     * Get the insights resource.
     */
    public function insights(): InsightsResource;

    /**
     * Create a new post builder.
     */
    public function create(): PostBuilder;

    /**
     * Quick publish a post to all configured accounts.
     *
     * @param  string|null  $mediaPath  Optional path to media file
     */
    public function publish(string $content, ?string $mediaPath = null): Post;

    /**
     * Check if the client is configured with a token.
     */
    public function isConfigured(): bool;

    /**
     * Get the configured account IDs.
     *
     * @return array<int>
     */
    public function getAccountIds(): array;

    /**
     * Make a GET request to the API.
     *
     * @param  array<string, mixed>  $query
     * @return array<array-key, mixed>
     */
    public function get(string $endpoint, array $query = []): array;

    /**
     * Make a POST request to the API.
     *
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>
     */
    public function post(string $endpoint, array $data = []): array;

    /**
     * Make a PATCH request to the API.
     *
     * @param  array<string, mixed>  $data
     * @return array<array-key, mixed>
     */
    public function patch(string $endpoint, array $data = []): array;

    /**
     * Make a DELETE request to the API.
     *
     * @return array<array-key, mixed>
     */
    public function delete(string $endpoint): array;
}
