<?php

declare(strict_types=1);

namespace Hei\SocialBu\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload  The raw webhook payload.
     */
    public function __construct(
        public readonly int $accountId,
        public readonly string $accountType,
        public readonly string $accountName,
        public readonly string $action,
        public readonly array $payload = [],
    ) {}
}
