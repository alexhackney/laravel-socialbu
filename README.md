# Laravel SocialBu

A Laravel package for the [SocialBu](https://socialbu.com) social media management API. Publish posts, upload media, manage accounts, and handle webhooks.

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

```bash
composer require alexhackney/laravel-socialbu
```

Publish the config:

```bash
php artisan vendor:publish --tag=socialbu-config
```

Add your credentials to `.env`:

```env
SOCIALBU_TOKEN=your-api-token
SOCIALBU_ACCOUNT_IDS=123,456
```

## Usage

### Quick Publish

```php
use Hei\SocialBu\Facades\SocialBu;

// Text post to all configured accounts
SocialBu::publish('Hello world!');

// With an image
SocialBu::publish('Check this out!', '/path/to/image.jpg');
```

### Fluent Builder

For more control, use the builder:

```php
SocialBu::create()
    ->content('Big announcement!')
    ->media('/path/to/image.jpg')
    ->media('https://example.com/video.mp4')
    ->to(123, 456)
    ->scheduledAt('2025-06-15 14:00:00')
    ->send();

// Save as draft
SocialBu::create()
    ->content('Work in progress')
    ->asDraft()
    ->send();

// Validate without sending
$payload = SocialBu::create()
    ->content('Test post')
    ->dryRun();
```

The builder accepts Carbon instances, DateTime objects, or date strings for scheduling. Account IDs default to your `.env` config but can be overridden per-post with `->to()`.

### Posts

```php
$posts = SocialBu::posts()->list();
$posts = SocialBu::posts()->list(type: 'scheduled', page: 2);

$post = SocialBu::posts()->get(123);

$post = SocialBu::posts()->create(
    content: 'Hello!',
    accountIds: [1, 2],
    publishAt: '2025-06-15 14:00:00',
);

SocialBu::posts()->update(123, ['content' => 'Updated!']);
SocialBu::posts()->delete(123);
```

Pagination:

```php
$page = SocialBu::posts()->paginate(perPage: 20);
// $page->items, $page->currentPage, $page->lastPage, $page->total

// Memory-efficient iteration over all posts
foreach (SocialBu::posts()->lazy() as $post) {
    echo $post->content;
}
```

### Accounts

```php
$accounts = SocialBu::accounts()->list();
$account = SocialBu::accounts()->get(123);

$account->isActive();
$account->requiresMedia(); // true for Instagram, TikTok, Pinterest
$account->isTwitter();     // also matches 'x'
```

### Media Upload

Media uploads use a 3-step signed URL flow (request signed URL, upload to S3, confirm). The package handles this automatically:

```php
// Local file
$media = SocialBu::media()->upload('/path/to/image.jpg');

// Remote URL
$media = SocialBu::media()->upload('https://example.com/photo.jpg');

// Attach to a post
SocialBu::posts()->create(
    content: 'With media!',
    accountIds: [1],
    attachments: [$media->toAttachment()],
);
```

The builder's `->media()` method handles uploads for you, so you typically don't need to call this directly.

## Error Handling

All exceptions extend `SocialBuException` and include request/response context for debugging:

```php
use Hei\SocialBu\Exceptions\AuthenticationException;
use Hei\SocialBu\Exceptions\ValidationException;
use Hei\SocialBu\Exceptions\RateLimitException;
use Hei\SocialBu\Exceptions\NotFoundException;
use Hei\SocialBu\Exceptions\ServerException;
use Hei\SocialBu\Exceptions\MediaUploadException;

try {
    SocialBu::publish('Hello!');
} catch (AuthenticationException $e) {
    // 401 - invalid or missing token
} catch (ValidationException $e) {
    $e->errors(); // ['field' => ['message', ...]]
} catch (RateLimitException $e) {
    $e->retryAfter(); // seconds until reset, or null
} catch (NotFoundException $e) {
    // 404
} catch (ServerException $e) {
    // 5xx
} catch (MediaUploadException $e) {
    $e->getStep(); // 'signed_url', 's3_upload', or 'confirmation'
}

// All exceptions provide logging context
Log::error('SocialBu failed', $e->context());
```

## Webhooks

Enable in config to receive post and account status updates:

```php
// config/socialbu.php
'webhooks' => [
    'enabled' => true,
    'prefix' => 'webhooks/socialbu',
    'middleware' => ['api'],
],
```

This registers two routes:

- `POST /webhooks/socialbu/post` -- post status updates
- `POST /webhooks/socialbu/account` -- account status updates

Listen for the dispatched events:

```php
use Hei\SocialBu\Events\PostStatusChanged;
use Hei\SocialBu\Events\AccountStatusChanged;

// In a listener
public function handle(PostStatusChanged $event): void
{
    $event->postId;
    $event->accountId;
    $event->status;    // 'published', 'failed', etc.
    $event->payload;   // full webhook data
}
```

## Artisan Commands

```bash
# List connected accounts
php artisan socialbu:accounts
php artisan socialbu:accounts --json

# Send a test post
php artisan socialbu:test "Hello from CLI!"
php artisan socialbu:test "With image" --media=/path/to/image.jpg
php artisan socialbu:test "Later" --schedule="2025-12-25 09:00:00"
php artisan socialbu:test "Preview" --dry-run
php artisan socialbu:test "Specific" --to=123 --to=456

# Get post details
php artisan socialbu:post 12345
php artisan socialbu:post 12345 --json
```

## Testing

The package ships with `FakeSocialBu` for testing your application code without hitting the API:

```php
use Hei\SocialBu\Testing\FakeSocialBu;

test('it shares to social media', function () {
    $fake = FakeSocialBu::fake();

    // ... your application code that calls SocialBu ...

    $fake->assertPublished('Hello!');
    $fake->assertPublishedCount(1);
    $fake->assertPublishedTo([123, 456]);
    $fake->assertUploaded('/path/to/image.jpg');
    $fake->assertUploadedCount(1);
    $fake->assertNothingPublished();
});
```

Simulate errors:

```php
use Hei\SocialBu\Exceptions\PostCreationException;

test('it handles publish failures', function () {
    $fake = FakeSocialBu::fake()
        ->throwOnPublish(new PostCreationException('API down'));

    // ... test your error handling ...
});
```

Seed fake data:

```php
$fake = FakeSocialBu::fake()
    ->withAccounts([
        ['id' => 1, 'name' => 'My Page', 'type' => 'facebook'],
    ])
    ->withPosts([
        ['id' => 100, 'content' => 'Existing post', 'created_at' => now()],
    ]);

$accounts = $fake->accounts()->list(); // returns seeded accounts
```

## Configuration Reference

```php
// config/socialbu.php
return [
    'token' => env('SOCIALBU_TOKEN'),

    'account_ids' => [], // parsed from SOCIALBU_ACCOUNT_IDS (comma-separated)

    'base_url' => env('SOCIALBU_BASE_URL', 'https://socialbu.com/api/v1'),

    'webhooks' => [
        'enabled' => env('SOCIALBU_WEBHOOKS_ENABLED', false),
        'prefix' => env('SOCIALBU_WEBHOOKS_PREFIX', 'webhooks/socialbu'),
        'middleware' => ['api'],
    ],

    'http' => [
        'timeout' => env('SOCIALBU_TIMEOUT', 30),
        'connect_timeout' => env('SOCIALBU_CONNECT_TIMEOUT', 10),
    ],
];
```

## License

MIT. See [LICENSE](LICENSE).
