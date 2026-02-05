<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Throwable;

class RateLimitException extends SocialBuException
{
    public function __construct(
        string $message = 'Rate limit exceeded.',
        protected ?int $retryAfter = null,
        ?array $response = null,
        ?array $request = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 429, $response, $request, $previous);
    }

    /**
     * Seconds until the rate limit resets, if provided by the API.
     */
    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function context(): array
    {
        return array_merge(parent::context(), [
            'retry_after' => $this->retryAfter,
        ]);
    }
}
