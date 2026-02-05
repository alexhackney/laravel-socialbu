<?php

declare(strict_types=1);

namespace Hei\SocialBu\Data;

final readonly class MediaUpload
{
    public function __construct(
        public string $uploadToken,
        public string $key,
        public string $url,
        public string $secureKey,
        public string $mimeType,
        public string $name,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            uploadToken: $data['upload_token'] ?? $data['uploadToken'] ?? '',
            key: $data['key'] ?? '',
            url: $data['url'] ?? '',
            secureKey: $data['secure_key'] ?? $data['secureKey'] ?? '',
            mimeType: $data['mime_type'] ?? $data['mimeType'] ?? 'application/octet-stream',
            name: $data['name'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'upload_token' => $this->uploadToken,
            'key' => $this->key,
            'url' => $this->url,
            'secure_key' => $this->secureKey,
            'mime_type' => $this->mimeType,
            'name' => $this->name,
        ];
    }

    /**
     * Get the payload for attaching to a post.
     */
    public function toAttachment(): array
    {
        return [
            'upload_token' => $this->uploadToken,
        ];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mimeType, 'video/');
    }
}
