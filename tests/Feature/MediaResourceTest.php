<?php

declare(strict_types=1);

use Hei\SocialBu\Client\SocialBuClient;
use Hei\SocialBu\Data\MediaUpload;
use Hei\SocialBu\Exceptions\MediaUploadException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new SocialBuClient(
        token: 'test-token',
        accountIds: [123, 456],
    );

    // Create a temp file for testing
    $this->tempFile = tempnam(sys_get_temp_dir(), 'socialbu_test_');
    file_put_contents($this->tempFile, 'fake image content');
});

afterEach(function () {
    if (file_exists($this->tempFile)) {
        unlink($this->tempFile);
    }
});

test('upload completes 3-step flow successfully', function () {
    Http::fake([
        '*/upload_media' => Http::response($this->fixture('upload-signed.json')),
        's3.amazonaws.com/*' => Http::response('', 200),
        '*/upload_media/status*' => Http::response($this->fixture('upload-status.json')),
    ]);

    $result = $this->client->media()->upload($this->tempFile);

    expect($result)->toBeInstanceOf(MediaUpload::class);
    expect($result->uploadToken)->toBe('upload-token-789');
    expect($result->key)->toBe('uploads/abc123.jpg');
    expect($result->url)->toBe('https://cdn.socialbu.com/uploads/abc123.jpg');
});

test('upload sends correct payload in step 1', function () {
    Http::fake([
        '*/upload_media' => Http::response($this->fixture('upload-signed.json')),
        's3.amazonaws.com/*' => Http::response('', 200),
        '*/upload_media/status*' => Http::response($this->fixture('upload-status.json')),
    ]);

    $this->client->media()->upload($this->tempFile);

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/upload_media')) {
            return true;
        }
        if (str_contains($request->url(), '/status')) {
            return true;
        }

        return $request->method() === 'POST'
            && isset($request['name'])
            && isset($request['mime_type']);
    });
});

test('upload sends file to S3 in step 2', function () {
    Http::fake([
        '*/upload_media' => Http::response($this->fixture('upload-signed.json')),
        's3.amazonaws.com/*' => Http::response('', 200),
        '*/upload_media/status*' => Http::response($this->fixture('upload-status.json')),
    ]);

    $this->client->media()->upload($this->tempFile);

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), 's3.amazonaws.com')) {
            return true;
        }

        return $request->method() === 'PUT'
            && $request->hasHeader('Content-Type')
            && $request->hasHeader('x-amz-acl', 'private');
    });
});

test('upload confirms with key in step 3', function () {
    Http::fake([
        '*/upload_media' => Http::response($this->fixture('upload-signed.json')),
        's3.amazonaws.com/*' => Http::response('', 200),
        '*/upload_media/status*' => Http::response($this->fixture('upload-status.json')),
    ]);

    $this->client->media()->upload($this->tempFile);

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/upload_media/status')) {
            return true;
        }

        return $request->method() === 'GET'
            && str_contains($request->url(), 'key=uploads/abc123.jpg');
    });
});

test('upload throws at signed_url step on API error', function () {
    Http::fake([
        '*/upload_media' => Http::response(['message' => 'Unauthorized'], 401),
    ]);

    try {
        $this->client->media()->upload($this->tempFile);
    } catch (MediaUploadException $e) {
        expect($e->getStep())->toBe(MediaUploadException::STEP_SIGNED_URL);

        throw $e;
    }
})->throws(MediaUploadException::class);

test('upload throws at s3_upload step on storage error', function () {
    Http::fake([
        '*/upload_media' => Http::response($this->fixture('upload-signed.json')),
        's3.amazonaws.com/*' => Http::response('Access Denied', 403),
    ]);

    try {
        $this->client->media()->upload($this->tempFile);
    } catch (MediaUploadException $e) {
        expect($e->getStep())->toBe(MediaUploadException::STEP_S3_UPLOAD);

        throw $e;
    }
})->throws(MediaUploadException::class);

test('upload throws at confirmation step on status error', function () {
    Http::fake([
        '*/upload_media' => Http::response($this->fixture('upload-signed.json')),
        's3.amazonaws.com/*' => Http::response('', 200),
        '*/upload_media/status*' => Http::response(['message' => 'Not found'], 404),
    ]);

    try {
        $this->client->media()->upload($this->tempFile);
    } catch (MediaUploadException $e) {
        expect($e->getStep())->toBe(MediaUploadException::STEP_CONFIRMATION);

        throw $e;
    }
})->throws(MediaUploadException::class);

test('upload throws for non-existent file', function () {
    $this->client->media()->upload('/path/to/nonexistent/file.jpg');
})->throws(MediaUploadException::class, 'File not found');

test('upload handles remote URL', function () {
    Http::fake([
        'https://example.com/image.jpg' => Http::response('fake image data', 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Length' => '15',
        ]),
        '*/upload_media' => Http::response($this->fixture('upload-signed.json')),
        's3.amazonaws.com/*' => Http::response('', 200),
        '*/upload_media/status*' => Http::response($this->fixture('upload-status.json')),
    ]);

    $result = $this->client->media()->upload('https://example.com/image.jpg');

    expect($result)->toBeInstanceOf(MediaUpload::class);
    expect($result->uploadToken)->toBe('upload-token-789');
});
