# Laravel SocialBu

A Laravel package for the [SocialBu](https://socialbu.com) social media management API. Publish posts, upload media, manage accounts, and handle webhooks.

## Requirements

- PHP 8.2+
- Laravel 12.x or 13.x

Laravel 13 requires PHP 8.3 or newer, so PHP 8.2 resolves to Laravel 12. The
supported combinations are:

| PHP | Laravel |
| --- | --- |
| 8.2 | 12.x |
| 8.3 / 8.4 / 8.5 | 12.x or 13.x |

Laravel 11 is no longer supported. Its entire release line is affected by an
unpatched security advisory, so Composer refuses to install it under the
default advisory policy.

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

// Release a scheduled or draft post immediately
$post = SocialBu::posts()->publishNow(123);

// Delete many posts in one request
SocialBu::posts()->bulkDelete([123, 124, 125]);
```

`publishNow()` is named to stay distinct from `SocialBu::publish()`, which creates
a new post rather than releasing one that already exists.

### Supported Post Options

Every network accepts a different `options` payload. Ask the API what each
account supports instead of guessing:

```php
$supported = SocialBu::posts()->supportedOptions();        // all accounts
$supported = SocialBu::posts()->supportedOptions([123]);   // specific accounts

$youtube = $supported[123];                 // keyed by account ID

$youtube->keys();                           // ['video_title', 'privacy_status', ...]
$youtube->supports('video_title');          // true
$youtube->requiredOptions();                // only the ones you must provide

$privacy = $youtube->option('privacy_status');
$privacy->label;                            // 'Privacy Status'
$privacy->type;                             // 'dropdown'
$privacy->isDropdown();                     // true
$privacy->choiceValues();                   // ['public', 'private', 'unlisted']
$privacy->defaultValue;                     // 'public'
$privacy->maxLength;                        // null
```

Check a payload before spending a request on a post the platform would reject.
`validate()` returns a map of option key to the reason it failed, so an empty
array means you are good:

```php
$errors = $youtube->validate([
    'video_title' => 'My video',
    'privacy_status' => 'secret',
]);

// ['privacy_status' => "Option 'privacy_status' must be one of: public, private, unlisted."]

if ($errors === []) {
    SocialBu::posts()->create(
        content: 'Hello!',
        accountIds: [123],
        options: ['video_title' => 'My video', 'privacy_status' => 'public'],
    );
}
```

It catches unsupported keys, invalid dropdown values, strings over `max_length`,
and missing required options.

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

// Remote URL -- downloads through your app, then runs the 3-step flow
$media = SocialBu::media()->upload('https://example.com/photo.jpg');

// Attach to a post
SocialBu::posts()->create(
    content: 'With media!',
    accountIds: [1],
    attachments: [$media->toAttachment()],
);
```

For a URL that SocialBu can reach itself, `uploadByUrl()` is far cheaper --
SocialBu fetches the file and returns the token in **one request** instead of
five, with nothing downloaded through your server and no temp file:

```php
$media = SocialBu::media()->uploadByUrl('https://example.com/photo.jpg');

// Optionally override the stored filename
$media = SocialBu::media()->uploadByUrl('https://example.com/photo.jpg', 'renamed.jpg');

$media->uploadToken;   // interchangeable with the 3-step flow's token
$media->toAttachment();
```

The tradeoff is reachability: `uploadByUrl()` needs a publicly accessible URL,
while `upload()` streams the file through your application and so also works for
URLs only your own network can see. Supported types are jpg, jpeg, png, gif,
webp, mp4, mov, avi, webm, mkv, and pdf, up to 500 MB.

Failures raise `MediaUploadException` with `getStep()` returning `'url_upload'`.

The builder's `->media()` method handles uploads for you, so you typically don't
need to call this directly. It currently uses `upload()` for every path,
including remote URLs.

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
    'secret' => env('SOCIALBU_WEBHOOK_SECRET'),
],
```

This registers two routes:

- `POST /webhooks/socialbu/post` -- post status updates
- `POST /webhooks/socialbu/account` -- account status updates

Keep the `api` middleware group. Laravel 13 renamed the CSRF middleware to
`PreventRequestForgery` and added `Sec-Fetch-Site` origin verification on top of
token checking, so putting these routes in the `web` group will reject SocialBu's
callbacks -- they are server-to-server and carry no session or CSRF token.

Set `SOCIALBU_WEBHOOK_SECRET` to verify authenticity. When present, the
controller compares the `X-SocialBu-Signature` header against an HMAC-SHA256 of
the raw request body and returns 403 on a mismatch. Leaving it unset skips
verification, which is not recommended in production.

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
    $fake->assertPublishedNow(123);
    $fake->assertDeleted(123);
    $fake->assertDeletedCount(3);
});
```

`assertDeleted()` covers both `delete()` and `bulkDelete()`. Seed supported
options with `withSupportedOptions()`:

```php
$fake = FakeSocialBu::fake()->withSupportedOptions([
    [
        'account_id' => 123,
        'account_type' => 'youtube',
        'account_name' => 'My Channel',
        'options' => [
            'video_title' => ['label' => 'Video Title', 'type' => 'string', 'required' => true, 'max_length' => 100],
        ],
    ],
]);

$fake->posts()->supportedOptions()[123]->option('video_title')->required; // true
```

Simulate errors:

```php
use Hei\SocialBu\Exceptions\SocialBuException;

test('it handles publish failures', function () {
    $fake = FakeSocialBu::fake()
        ->throwOnPublish(new SocialBuException('API down'));

    // ... test your error handling ...
});
```

`throwOnPublish()` and `throwOnUpload()` accept any `Throwable`, so you can
simulate a specific failure with `AuthenticationException`, `RateLimitException`,
`ValidationException`, or `MediaUploadException` as needed.

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
        'secret' => env('SOCIALBU_WEBHOOK_SECRET'),
    ],

    'http' => [
        'timeout' => env('SOCIALBU_TIMEOUT', 30),
        'connect_timeout' => env('SOCIALBU_CONNECT_TIMEOUT', 10),
    ],
];
```

## License

MIT. See [LICENSE](LICENSE).
