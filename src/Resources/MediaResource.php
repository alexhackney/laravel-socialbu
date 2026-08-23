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
     * Upload a media file by handing SocialBu a publicly reachable URL.
     *
     * SocialBu downloads the file itself and returns an upload token in a
     * single request, so this avoids the three-step signed URL flow entirely --
     * no HEAD request, no download through your server, no temp file, and no
     * status check. The returned token is interchangeable with the one from
     * upload().
     *
     * The URL must be reachable from SocialBu's servers, which is the tradeoff
     * against upload(): that streams the file through your application, so it
     * also works for URLs only your network can see.
     *
     * Supported types: jpg, jpeg, png, gif, webp, mp4, mov, avi, webm, mkv,
     * pdf. Maximum 500 MB.
     *
     * @param  string|null  $name  Custom filename; derived from the URL if omitted.
     *
     * @throws MediaUploadException
     */
    public function uploadByUrl(string $url, ?string $name = null): MediaUpload
    {
        try {
            $response = $this->client->post('/upload_media_by_url', array_filter([
                'url' => $url,
                'name' => $name,
            ], fn ($value) => $value !== null));
        } catch (SocialBuException $e) {
            throw MediaUploadException::atStep(MediaUploadException::STEP_URL_UPLOAD, $e);
        }

        // SocialBu answers 200 with a null token while the file is still being
        // fetched, so an absent or blank token is a failure, not a success.
        $uploadToken = $response['upload_token'] ?? null;

        if (! is_string($uploadToken) || $uploadToken === '') {
            throw new MediaUploadException(
                'Media upload from URL did not return an upload token.',
                MediaUploadException::STEP_URL_UPLOAD,
                response: $response,
            );
        }

        return MediaUpload::fromArray($response);
    }

    /**
     * Get a signed URL for uploading.
     *
     * @return array<string, mixed> The signed_url, key, url and secure_key for the pending upload.
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
            $contents = is_resource($file->stream)
                ? stream_get_contents($file->stream)
                : false;

            if ($contents === false) {
                throw new SocialBuException("Could not read media file for upload: {$file->name}");
            }

            $response = Http::withHeaders([
                'Content-Type' => $file->mimeType,
                'Content-Length' => (string) $file->size,
                'x-amz-acl' => 'private',
            ])->withBody(
                $contents,
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
     * @param  array<string, mixed>  $signed  The response from getSignedUrl().
     *
     * @throws MediaUploadException
     */
    private function confirmUpload(array $signed, ResolvedFile $file): MediaUpload
    {
        try {
            $status = $this->client->get('/upload_media/status', [
                'key' => $signed['key'],
            ]);

            $uploadToken = $status['upload_token'] ?? null;

            if (! is_string($uploadToken) || $uploadToken === '') {
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
        } catch (MediaUploadException $e) {
            throw $e;
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

        // Response::header() returns an empty string for absent headers, so
        // null-coalescing never fires here, so fall back on emptiness instead.
        $contentLength = (int) $headResponse->header('Content-Length');
        $contentType = $this->normalizeMimeType($headResponse->header('Content-Type'));
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

    /**
     * Reduce a Content-Type header to a bare mime type.
     *
     * Strips any parameters ("image/jpeg; charset=binary") and falls back to a
     * generic type when the header is absent or unparseable, so we never send
     * an empty Content-Type on to the signed URL request.
     */
    private function normalizeMimeType(string $header): string
    {
        $mimeType = trim(explode(';', $header, 2)[0]);

        return $mimeType !== '' ? $mimeType : 'application/octet-stream';
    }
}
