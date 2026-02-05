<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Throwable;

class PostCreationException extends SocialBuException
{
    public function __construct(
        string $message = 'Failed to create post.',
        int $statusCode = 0,
        ?array $response = null,
        ?array $request = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $response, $request, $previous);
    }
}
