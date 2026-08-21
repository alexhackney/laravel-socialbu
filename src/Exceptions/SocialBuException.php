<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Exception;
use Throwable;

class SocialBuException extends Exception
{
    /**
     * @param  array<array-key, mixed>|null  $response  The decoded API response body.
     * @param  array<string, mixed>|null  $request  The request that failed: method, endpoint, data.
     */
    public function __construct(
        string $message,
        protected int $statusCode = 0,
        protected ?array $response = null,
        protected ?array $request = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequest(): ?array
    {
        return $this->request;
    }

    /**
     * Get context array for logging.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return array_filter([
            'status_code' => $this->statusCode,
            'response' => $this->response,
            'request' => $this->request,
        ]);
    }
}
