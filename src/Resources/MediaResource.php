<?php

declare(strict_types=1);

namespace Hei\SocialBu\Resources;

use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\MediaUpload;
use Hei\SocialBu\Data\ResolvedFile;
use Hei\SocialBu\Exceptions\MediaUploadException;
use Hei\SocialBu\Exceptions\SocialBuException;
use Illuminate\Support\Facades\Http;
use Throwable;

class MediaResource
{
    public function __construct(
        private readonly SocialBuClientInterface $client,
    ) {}

    /**
     * Upload a media file.
     *
     * Supports local file paths and remote URLs.
     *
     * @throws MediaUploadException
     */
    public function upload(string $path): MediaUpload
    {
        $file = $this->resolveFile($path);

        try {
            // Step 1: Get signed URL from SocialBu
            $signed = $this->getSignedUrl($file);

            // Step 2: Upload file to S3 via signed URL
            $this->uploadToStorage($signed['signed_url'], $file);

            // Step 3: Confirm upload and get token
            return $this->confirmUpload($signed, $file);
        } finally {
            $file->close();
        }
    }

    /**
     * Get a signed URL for uploading.
     *
     * @throws MediaUploadException
     */
    private function getSignedUrl(ResolvedFile $file): array
    {
        try {
            return $this->client->post('/upload_media', [
                'name' => $file->name,
                'mime_type' => $file->mimeType,
            ]);
        } catch (SocialBuException $e) {
            throw MediaUploadException::atStep(MediaUploadException::STEP_SIGNED_URL, $e);
        }
    }

    /**
     * Upload file content to storage via signed URL.
     *
     * @throws MediaUploadException
     */
    private function uploadToStorage(string $signedUrl, ResolvedFile $file): void
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => $file->mimeType,
                'Content-Length' => (string) $file->size,
                'x-amz-acl' => 'private',
            ])->withBody(
                stream_get_contents($file->stream),
                $file->mimeType
            )->put($signedUrl);

            if (! $response->successful()) {
                throw new SocialBuException(
                    'Failed to upload to storage: '.$response->body(),
                    $response->status(),
                );
            }
        } catch (SocialBuException $e) {
            throw MediaUploadException::atStep(MediaUploadException::STEP_S3_UPLOAD, $e);
        } catch (Throwable $e) {
            throw MediaUploadException::atStep(
                MediaUploadException::STEP_S3_UPLOAD,
                new SocialBuException($e->getMessage(), previous: $e),
            );
        }
    }

    /**
     * Confirm the upload and get the upload token.
     *
     * @throws MediaUploadException
     */
    private function confirmUpload(array $signed, ResolvedFile $file): MediaUpload
    {
        try {
            $status = $this->client->get('/upload_media/status', [
                'key' => $signed['key'],
            ]);

            $uploadToken = $status['upload_token'] ?? '';

            if ($uploadToken === '' || $uploadToken === null) {
                throw new MediaUploadException(
                    'Media upload confirmation did not return an upload token. The file may still be processing.',
                    MediaUploadException::STEP_CONFIRMATION,
                );
            }

            return new MediaUpload(
                uploadToken: $uploadToken,
                key: $signed['key'] ?? '',
                url: $signed['url'] ?? '',
                secureKey: $signed['secure_key'] ?? '',
                mimeType: $file->mimeType,
                name: $file->name,
            );
        } catch (SocialBuException $e) {
            throw MediaUploadException::atStep(MediaUploadException::STEP_CONFIRMATION, $e);
        }
    }

    /**
     * Resolve file metadata and create a stream.
     *
     * @throws MediaUploadException
     */
    private function resolveFile(string $path): ResolvedFile
    {
        // Local file
        if (file_exists($path)) {
            return $this->resolveLocalFile($path);
        }

        // Remote URL
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $this->resolveRemoteFile($path);
        }

        throw new MediaUploadException(
            "File not found: {$path}",
            MediaUploadException::STEP_SIGNED_URL,
        );
    }

    /**
     * Resolve a local file.
     */
    private function resolveLocalFile(string $path): ResolvedFile
    {
        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new MediaUploadException(
                "Cannot open file: {$path}",
                MediaUploadException::STEP_SIGNED_URL,
            );
        }

        return new ResolvedFile(
            name: basename($path),
            mimeType: mime_content_type($path) ?: 'application/octet-stream',
            size: filesize($path) ?: 0,
            stream: $stream,
            path: $path,
        );
    }

    /**
     * Resolve a remote file via URL.
     */
    private function resolveRemoteFile(string $url): ResolvedFile
    {
        // Get file info via HEAD request
        $headResponse = Http::head($url);

        if (! $headResponse->successful()) {
            throw new MediaUploadException(
                "Cannot access remote file: {$url}",
                MediaUploadException::STEP_SIGNED_URL,
            );
        }

        $contentLength = (int) ($headResponse->header('Content-Length') ?? 0);
        $contentType = $headResponse->header('Content-Type') ?? 'application/octet-stream';
        $name = basename(parse_url($url, PHP_URL_PATH) ?: 'file');

        // Stream remote file to a temp file to avoid loading into memory
        $tempFile = tempnam(sys_get_temp_dir(), 'socialbu_');

        $response = Http::withOptions(['sink' => $tempFile])->get($url);

        if (! $response->successful()) {
            @unlink($tempFile);

            throw new MediaUploadException(
                "Cannot download remote file: {$url}",
                MediaUploadException::STEP_SIGNED_URL,
            );
        }

        $stream = fopen($tempFile, 'rb');

        if ($stream === false) {
            @unlink($tempFile);

            throw new MediaUploadException(
                "Cannot open downloaded file: {$url}",
                MediaUploadException::STEP_SIGNED_URL,
            );
        }

        $size = $contentLength > 0 ? $contentLength : (int) filesize($tempFile);

        return new ResolvedFile(
            name: $name,
            mimeType: $contentType,
            size: $size,
            stream: $stream,
            path: $tempFile,
        );
    }
}
