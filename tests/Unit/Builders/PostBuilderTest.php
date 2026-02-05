<?php

declare(strict_types=1);

use Carbon\Carbon;
use Hei\SocialBu\Builders\PostBuilder;
use Hei\SocialBu\Client\SocialBuClient;
use Hei\SocialBu\Exceptions\ValidationException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new SocialBuClient(
        token: 'test-token',
        accountIds: [100, 200],
    );
});

test('it builds post with content', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Hello world!')
        ->dryRun();

    expect($payload['content'])->toBe('Hello world!');
});

test('it uses default account ids when none specified', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->dryRun();

    expect($payload['accounts'])->toBe([100, 200]);
});

test('it overrides account ids with to()', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->to(1, 2, 3)
        ->dryRun();

    expect($payload['accounts'])->toBe([1, 2, 3]);
});

test('to() accepts array', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->to([1, 2, 3])
        ->dryRun();

    expect($payload['accounts'])->toBe([1, 2, 3]);
});

test('to() can be chained and merges account ids', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->to(1, 2)
        ->to([3, 4])
        ->to(5)
        ->dryRun();

    expect($payload['accounts'])->toBe([1, 2, 3, 4, 5]);
});

test('to() deduplicates account ids', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->to(1, 2, 1, 3, 2)
        ->dryRun();

    expect($payload['accounts'])->toBe([1, 2, 3]);
});

test('scheduledAt accepts string', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->scheduledAt('2025-06-15 14:00:00')
        ->dryRun();

    expect($payload['publish_at'])->toBe('2025-06-15 14:00:00');
});

test('scheduledAt accepts Carbon', function () {
    $builder = new PostBuilder($this->client);
    $date = Carbon::parse('2025-06-15 14:00:00');

    $payload = $builder
        ->content('Test')
        ->scheduledAt($date)
        ->dryRun();

    expect($payload['publish_at'])->toBe('2025-06-15 14:00:00');
});

test('scheduledAt accepts DateTime', function () {
    $builder = new PostBuilder($this->client);
    $date = new DateTime('2025-06-15 14:00:00');

    $payload = $builder
        ->content('Test')
        ->scheduledAt($date)
        ->dryRun();

    expect($payload['publish_at'])->toBe('2025-06-15 14:00:00');
});

test('schedule is alias for scheduledAt', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->schedule('2025-06-15 14:00:00')
        ->dryRun();

    expect($payload['publish_at'])->toBe('2025-06-15 14:00:00');
});

test('asDraft sets draft flag', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->asDraft()
        ->dryRun();

    expect($payload['draft'])->toBeTrue();
});

test('withPostbackUrl sets postback url', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->withPostbackUrl('https://example.com/webhook')
        ->dryRun();

    expect($payload['postback_url'])->toBe('https://example.com/webhook');
});

test('media adds media path', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->media('/path/to/image.jpg')
        ->dryRun();

    expect($payload['pending_uploads'])->toBe(['/path/to/image.jpg']);
});

test('media can be called multiple times', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->media('/path/to/image1.jpg')
        ->media('/path/to/image2.jpg')
        ->dryRun();

    expect($payload['pending_uploads'])->toBe([
        '/path/to/image1.jpg',
        '/path/to/image2.jpg',
    ]);
});

test('dryRun throws when content is empty', function () {
    $builder = new PostBuilder($this->client);

    $builder->dryRun();
})->throws(ValidationException::class);

test('dryRun throws when content is whitespace only', function () {
    $builder = new PostBuilder($this->client);

    $builder->content('   ')->dryRun();
})->throws(ValidationException::class);

test('dryRun throws when no accounts available', function () {
    $client = new SocialBuClient(token: 'test', accountIds: []);
    $builder = new PostBuilder($client);

    $builder->content('Test')->dryRun();
})->throws(ValidationException::class);

test('validation exception includes field errors', function () {
    $client = new SocialBuClient(token: 'test', accountIds: []);
    $builder = new PostBuilder($client);

    try {
        $builder->dryRun();
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('content');
        expect($e->errors())->toHaveKey('accounts');
    }
});

test('send creates post via API', function () {
    Http::fake([
        '*/posts*' => Http::response([
            'success' => true,
            'posts' => [
                [
                    'id' => 123,
                    'content' => 'Hello!',
                    'status' => 'scheduled',
                    'account_ids' => [100, 200],
                    'created_at' => '2025-01-15 10:00:00',
                ],
            ],
        ]),
    ]);

    $post = $this->client->create()
        ->content('Hello!')
        ->scheduledAt('2025-06-15 14:00:00')
        ->send();

    expect($post->id)->toBe(123);
    expect($post->content)->toBe('Hello!');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request['content'] === 'Hello!'
            && $request['publish_at'] === '2025-06-15 14:00:00';
    });
});

test('send uploads media before creating post', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'socialbu_test_');
    file_put_contents($tempFile, 'fake image');

    try {
        Http::fake([
            '*/upload_media' => Http::response([
                'signed_url' => 'https://s3.example.com/upload',
                'key' => 'uploads/test.jpg',
                'url' => 'https://cdn.example.com/test.jpg',
                'secure_key' => 'secure-123',
            ]),
            's3.example.com/*' => Http::response('', 200),
            '*/upload_media/status*' => Http::response([
                'success' => true,
                'upload_token' => 'token-456',
            ]),
            '*/posts*' => Http::response([
                'success' => true,
                'posts' => [
                    [
                        'id' => 123,
                        'content' => 'With media!',
                        'status' => 'published',
                        'account_ids' => [100],
                        'created_at' => '2025-01-15 10:00:00',
                    ],
                ],
            ]),
        ]);

        $this->client->create()
            ->content('With media!')
            ->media($tempFile)
            ->send();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/posts')) {
                return true;
            }
            if (str_contains($request->url(), '/upload')) {
                return true;
            }

            return isset($request['existing_attachments'])
                && $request['existing_attachments'][0]['upload_token'] === 'token-456';
        });
    } finally {
        @unlink($tempFile);
    }
});

test('withOptions sets platform options', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->withOptions(['title' => 'Reddit Title', 'privacy_status' => 'public'])
        ->dryRun();

    expect($payload['options'])->toBe(['title' => 'Reddit Title', 'privacy_status' => 'public']);
});

test('withOptions is chainable', function () {
    $builder = new PostBuilder($this->client);

    $result = $builder
        ->content('Test')
        ->withOptions(['title' => 'Test']);

    expect($result)->toBeInstanceOf(PostBuilder::class);
});

test('dryRun omits options when not set', function () {
    $builder = new PostBuilder($this->client);

    $payload = $builder
        ->content('Test')
        ->dryRun();

    expect($payload)->not->toHaveKey('options');
});

test('fluent interface is chainable', function () {
    $builder = new PostBuilder($this->client);

    $result = $builder
        ->content('Test')
        ->media('/path/to/image.jpg')
        ->to(1, 2, 3)
        ->scheduledAt('2025-06-15 14:00:00')
        ->asDraft()
        ->withPostbackUrl('https://example.com/hook');

    expect($result)->toBeInstanceOf(PostBuilder::class);
});
