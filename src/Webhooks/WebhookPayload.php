<?php

declare(strict_types=1);

namespace Hei\SocialBu\Webhooks;

final readonly class WebhookPayload
{
    public function __construct(
        public string $type,
        public array $data,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? $data['event'] ?? 'unknown',
            data: $data['data'] ?? $data,
        );
    }

    public function isPostEvent(): bool
    {
        return str_starts_with($this->type, 'post.');
    }

    public function isAccountEvent(): bool
    {
        return str_starts_with($this->type, 'account.');
    }

    public function getPostId(): ?int
    {
        return isset($this->data['post_id']) ? (int) $this->data['post_id'] : null;
    }

    public function getAccountId(): ?int
    {
        return isset($this->data['account_id']) ? (int) $this->data['account_id'] : null;
    }

    public function getStatus(): ?string
    {
        return $this->data['status'] ?? null;
    }

    public function getAction(): ?string
    {
        return $this->data['action'] ?? null;
    }
}
