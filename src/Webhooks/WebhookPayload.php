<?php

declare(strict_types=1);

namespace Hei\SocialBu\Webhooks;

final readonly class WebhookPayload
{
    public function __construct(
        public array $data,
    ) {}

    public static function fromArray(array $data): self
    {
        // Webhook payloads are flat (no nesting under a 'data' key).
        // If a 'data' wrapper exists, unwrap it for backwards compatibility.
        return new self(
            data: $data['data'] ?? $data,
        );
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

    /**
     * Get the account action (added, updated, connected, disconnected).
     */
    public function getAccountAction(): ?string
    {
        return $this->data['account_action'] ?? $this->data['action'] ?? null;
    }

    /**
     * Get the account type from an account webhook.
     */
    public function getAccountType(): ?string
    {
        return $this->data['account_type'] ?? $this->data['type'] ?? $this->data['platform'] ?? null;
    }

    /**
     * Get the account name from an account webhook.
     */
    public function getAccountName(): ?string
    {
        return $this->data['account_name'] ?? $this->data['name'] ?? null;
    }
}
