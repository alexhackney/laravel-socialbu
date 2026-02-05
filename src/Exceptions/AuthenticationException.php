<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Throwable;

class AuthenticationException extends SocialBuException
{
    public function __construct(
        string $message = 'Authentication failed. Check your API token.',
        ?array $response = null,
        ?array $request = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 401, $response, $request, $previous);
    }
}
