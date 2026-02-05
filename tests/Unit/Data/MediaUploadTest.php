<?php

declare(strict_types=1);

use Hei\SocialBu\Data\MediaUpload;

test('it creates media upload from array', function () {
    $media = MediaUpload::fromArray([
        'upload_token' => 'token-123',
        'key' => 'uploads/abc123.jpg',
        'url' => 'https://cdn.example.com/abc123.jpg',
        'secure_key' => 'secure-key-456',
        'mime_type' => 'image/jpeg',
        'name' => 'photo.jpg',
    ]);

    expect($media->uploadToken)->toBe('token-123');
    expect($media->key)->toBe('uploads/abc123.jpg');
    expect($media->url)->toBe('https://cdn.example.com/abc123.jpg');
    expect($media->secureKey)->toBe('secure-key-456');
    expect($media->mimeType)->toBe('image/jpeg');
    expect($media->name)->toBe('photo.jpg');
});

test('it handles camelCase field names', function () {
    $media = MediaUpload::fromArray([
        'uploadToken' => 'token-123',
        'secureKey' => 'secure-456',
        'mimeType' => 'video/mp4',
    ]);

    expect($media->uploadToken)->toBe('token-123');
    expect($media->secureKey)->toBe('secure-456');
    expect($media->mimeType)->toBe('video/mp4');
});

test('it converts to array', function () {
    $media = MediaUpload::fromArray([
        'upload_token' => 'token-123',
        'key' => 'uploads/abc123.jpg',
        'url' => 'https://cdn.example.com/abc123.jpg',
        'secure_key' => 'secure-key-456',
        'mime_type' => 'image/jpeg',
        'name' => 'photo.jpg',
    ]);

    $array = $media->toArray();

    expect($array)->toBe([
        'upload_token' => 'token-123',
        'key' => 'uploads/abc123.jpg',
        'url' => 'https://cdn.example.com/abc123.jpg',
        'secure_key' => 'secure-key-456',
        'mime_type' => 'image/jpeg',
        'name' => 'photo.jpg',
    ]);
});

test('toAttachment returns correct structure for post', function () {
    $media = MediaUpload::fromArray([
        'upload_token' => 'token-123',
        'key' => 'uploads/abc123.jpg',
        'url' => 'https://cdn.example.com/abc123.jpg',
        'secure_key' => 'secure-key-456',
        'mime_type' => 'image/jpeg',
        'name' => 'photo.jpg',
    ]);

    expect($media->toAttachment())->toBe([
        'upload_token' => 'token-123',
    ]);
});

test('isImage returns true for image mime types', function () {
    expect(MediaUpload::fromArray(['mime_type' => 'image/jpeg'])->isImage())->toBeTrue();
    expect(MediaUpload::fromArray(['mime_type' => 'image/png'])->isImage())->toBeTrue();
    expect(MediaUpload::fromArray(['mime_type' => 'image/gif'])->isImage())->toBeTrue();
    expect(MediaUpload::fromArray(['mime_type' => 'image/webp'])->isImage())->toBeTrue();
});

test('isImage returns false for non-image mime types', function () {
    expect(MediaUpload::fromArray(['mime_type' => 'video/mp4'])->isImage())->toBeFalse();
    expect(MediaUpload::fromArray(['mime_type' => 'application/pdf'])->isImage())->toBeFalse();
});

test('isVideo returns true for video mime types', function () {
    expect(MediaUpload::fromArray(['mime_type' => 'video/mp4'])->isVideo())->toBeTrue();
    expect(MediaUpload::fromArray(['mime_type' => 'video/quicktime'])->isVideo())->toBeTrue();
    expect(MediaUpload::fromArray(['mime_type' => 'video/webm'])->isVideo())->toBeTrue();
});

test('isVideo returns false for non-video mime types', function () {
    expect(MediaUpload::fromArray(['mime_type' => 'image/jpeg'])->isVideo())->toBeFalse();
    expect(MediaUpload::fromArray(['mime_type' => 'application/pdf'])->isVideo())->toBeFalse();
});

test('it handles missing fields with defaults', function () {
    $media = MediaUpload::fromArray([]);

    expect($media->uploadToken)->toBe('');
    expect($media->key)->toBe('');
    expect($media->url)->toBe('');
    expect($media->secureKey)->toBe('');
    expect($media->mimeType)->toBe('application/octet-stream');
    expect($media->name)->toBe('');
});
