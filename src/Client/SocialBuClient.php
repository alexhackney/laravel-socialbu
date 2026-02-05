<?php

declare(strict_types=1);

namespace Hei\SocialBu\Client;

use Hei\SocialBu\Builders\PostBuilder;
use Hei\SocialBu\Data\Post;
use Hei\SocialBu\Exceptions\AuthenticationException;
use Hei\SocialBu\Exceptions\NotFoundException;
use Hei\SocialBu\Exceptions\RateLimitException;
use Hei\SocialBu\Exceptions\ServerException;
use Hei\SocialBu\Exceptions\SocialBuException;
use Hei\SocialBu\Exceptions\ValidationException;
use Hei\SocialBu\Resources\AccountResource;
use Hei\SocialBu\Resources\MediaResource;
use Hei\SocialBu\Resources\PostResource;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SocialBuClient implements SocialBuClientInterface
{
    private ?PostResource $postResource = null;

    private ?AccountResource $accountResource = null;

    private ?MediaResource $mediaResource = null;

    /**
     * @param  array<int>  $accountIds
     */
    public function __construct(
        private readonly ?string $token = null,
        private readonly array $accountIds = [],
        private readonly string $baseUrl = 'https://socialbu.com/api/v1',
        private readonly int $timeout = 30,
        private readonly int $connectTimeout = 10,
    ) {}

    public function posts(): PostResource
    {
        return $this->postResource ??= new PostResource($this);
    }

    public function accounts(): AccountResource
    {
        return $this->accountResource ??= new AccountResource($this);
    }

    public function media(): MediaResource
    {
        return $this->mediaResource ??= new MediaResource($this);
    }

    public function create(): PostBuilder
    {
        return new PostBuilder($this);
    }

    public function publish(string $content, ?string $mediaPath = null): Post
    {
        $builder = $this->create()->content($content);

        if ($mediaPath !== null) {
            $builder->media($mediaPath);
        }

        return $builder->send();
    }

    public function isConfigured(): bool
    {
        return $this->token !== null && $this->token !== '';
    }

    public function getAccountIds(): array
    {
        return $this->accountIds;
    }

    public function get(string $endpoint, array $query = []): array
    {
        $response = $this->request()->get($this->url($endpoint), $query);

        return $this->handleResponse($response, 'GET', $endpoint, $query);
    }

    public function post(string $endpoint, array $data = []): array
    {
        $response = $this->request()->post($this->url($endpoint), $data);

        return $this->handleResponse($response, 'POST', $endpoint, $data);
    }

    public function patch(string $endpoint, array $data = []): array
    {
        $response = $this->request()->patch($this->url($endpoint), $data);

        return $this->handleResponse($response, 'PATCH', $endpoint, $data);
    }

    public function delete(string $endpoint): array
    {
        $response = $this->request()->delete($this->url($endpoint));

        return $this->handleResponse($response, 'DELETE', $endpoint);
    }

    /**
     * Build the full URL for an endpoint.
     */
    private function url(string $endpoint): string
    {
        return rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');
    }

    /**
     * Create a configured HTTP request.
     */
    private function request(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->withToken($this->token ?? '')
            ->acceptJson();
    }

    /**
     * Handle the API response and throw exceptions for errors.
     *
     * @throws SocialBuException
     */
    private function handleResponse(
        Response $response,
        string $method,
        string $endpoint,
        array $data = [],
    ): array {
        $body = $response->json() ?? [];
        $request = [
            'method' => $method,
            'endpoint' => $endpoint,
            'data' => $data,
        ];

        if ($response->successful()) {
            return $body;
        }

        $message = $body['message'] ?? $body['error'] ?? 'Unknown error';

        throw match ($response->status()) {
            401 => new AuthenticationException($message, $body, $request),
            404 => new NotFoundException($message, $body, $request),
            422 => new ValidationException(
                $message,
                $body['errors'] ?? [],
                $body,
                $request,
            ),
            429 => new RateLimitException(
                $message,
                $this->parseRetryAfter($response),
                $body,
                $request,
            ),
            500, 502, 503, 504 => new ServerException(
                $message,
                $response->status(),
                $body,
                $request,
            ),
            default => new SocialBuException(
                $message,
                $response->status(),
                $body,
                $request,
            ),
        };
    }

    /**
     * Parse the Retry-After header from the response.
     */
    private function parseRetryAfter(Response $response): ?int
    {
        $retryAfter = $response->header('Retry-After');

        if ($retryAfter === null) {
            return null;
        }

        return (int) $retryAfter;
    }
}
