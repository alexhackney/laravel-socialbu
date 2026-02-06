<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data;

final readonly class Account
{
    /**
     * @param  array<string, mixed>|null  $extraData
     * @param  array<string>|null  $attachmentTypes
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
        public ?int $postMaxLength = null,
        public ?int $maxAttachments = null,
        public ?array $attachmentTypes = null,
        public ?bool $postMediaRequired = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            name: $data['name'] ?? '',
            type: $data['type'] ?? $data['platform'] ?? 'unknown',
            status: $data['status'] ?? (isset($data['active']) ? ($data['active'] ? 'active' : 'inactive') : 'active'),
            username: $data['username'] ?? null,
            profileUrl: $data['profile_url'] ?? $data['profileUrl'] ?? null,
            avatarUrl: $data['avatar_url'] ?? $data['avatarUrl'] ?? $data['avatar'] ?? $data['image'] ?? null,
            extraData: $data['extra_data'] ?? $data['extraData'] ?? null,
            postMaxLength: isset($data['post_maxlength']) ? (int) $data['post_maxlength'] : null,
            maxAttachments: isset($data['max_attachments']) ? (int) $data['max_attachments'] : null,
            attachmentTypes: $data['attachment_types'] ?? null,
            postMediaRequired: isset($data['post_media_required']) ? (bool) $data['post_media_required'] : null,
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
            'post_maxlength' => $this->postMaxLength,
            'max_attachments' => $this->maxAttachments,
            'attachment_types' => $this->attachmentTypes,
            'post_media_required' => $this->postMediaRequired,
        ], fn ($value) => $value !== null);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFacebook(): bool
    {
        return $this->type === 'facebook' || str_starts_with($this->type, 'facebook.');
    }

    public function isInstagram(): bool
    {
        return $this->type === 'instagram' || str_starts_with($this->type, 'instagram.');
    }

    public function isTwitter(): bool
    {
        return $this->type === 'twitter' || $this->type === 'x'
            || str_starts_with($this->type, 'twitter.')
            || str_starts_with($this->type, 'x.');
    }

    public function isLinkedIn(): bool
    {
        return $this->type === 'linkedin' || str_starts_with($this->type, 'linkedin.');
    }

    public function isTikTok(): bool
    {
        return $this->type === 'tiktok' || str_starts_with($this->type, 'tiktok.');
    }

    public function isPinterest(): bool
    {
        return $this->type === 'pinterest' || str_starts_with($this->type, 'pinterest.');
    }

    public function requiresMedia(): bool
    {
        if ($this->postMediaRequired !== null) {
            return $this->postMediaRequired;
        }

        return $this->isInstagram() || $this->isTikTok() || $this->isPinterest();
    }
}
