<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data;

final readonly class Account
{
    /**
     * @param  array<string, mixed>|null  $extraData
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $type,
        public string $status,
        public ?string $username = null,
        public ?string $profileUrl = null,
        public ?string $avatarUrl = null,
        public ?array $extraData = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: $data['name'] ?? '',
            type: $data['type'] ?? $data['platform'] ?? 'unknown',
            status: $data['status'] ?? 'active',
            username: $data['username'] ?? null,
            profileUrl: $data['profile_url'] ?? $data['profileUrl'] ?? null,
            avatarUrl: $data['avatar_url'] ?? $data['avatarUrl'] ?? $data['avatar'] ?? null,
            extraData: $data['extra_data'] ?? $data['extraData'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'username' => $this->username,
            'profile_url' => $this->profileUrl,
            'avatar_url' => $this->avatarUrl,
            'extra_data' => $this->extraData,
        ], fn ($value) => $value !== null);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFacebook(): bool
    {
        return $this->type === 'facebook';
    }

    public function isInstagram(): bool
    {
        return $this->type === 'instagram';
    }

    public function isTwitter(): bool
    {
        return $this->type === 'twitter' || $this->type === 'x';
    }

    public function isLinkedIn(): bool
    {
        return $this->type === 'linkedin';
    }

    public function isTikTok(): bool
    {
        return $this->type === 'tiktok';
    }

    public function requiresMedia(): bool
    {
        return in_array($this->type, ['instagram', 'tiktok', 'pinterest'], true);
    }
}
