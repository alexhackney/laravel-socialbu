<?php

declare(strict_types=1);

namespace Hei\SocialBu\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $postId,
        public readonly int $accountId,
        public readonly string $status,
        public readonly array $payload = [],
    ) {}
}
