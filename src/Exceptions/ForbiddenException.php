<?php

declare(strict_types=1);

namespace Hei\SocialBu\Exceptions;

use Throwable;

/**
 * SocialBu returns 403 both when a plan limit is exceeded (e.g. the monthly
 * post quota) and when the token lacks permission for the action. The API
 * message distinguishes the two; check getMessage() or getResponse().
 */
class ForbiddenException extends SocialBuException
{
    /**
     * @param  array<array-key, mixed>|null  $response
     * @param  array<string, mixed>|null  $request
     */
    public function __construct(
        string $message = 'Forbidden — plan limit exceeded or insufficient permissions.',
        ?array $response = null,
        ?array $request = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 403, $response, $request, $previous);
    }
}
