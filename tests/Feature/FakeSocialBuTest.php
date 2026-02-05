<?php

declare(strict_types=1);

use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\Account;
use Hei\SocialBu\Data\Post;
use Hei\SocialBu\Exceptions\SocialBuException;
use Hei\SocialBu\Testing\FakeSocialBu;

test('fake binds itself to container', function () {
    $fake = FakeSocialBu::fake();

    expect(app(SocialBuClientInterface::class))->toBe($fake);
});

test('fake records published posts', function () {
    $fake = FakeSocialBu::fake();

    $fake->create()
        ->content('Hello world!')
        ->to(1, 2, 3)
        ->send();

    expect($fake->getPublished())->toHaveCount(1);
    expect($fake->getPublished()[0]['content'])->toBe('Hello world!');
});

test('assertPublished passes when content was published', function () {
    $fake = FakeSocialBu::fake();

    $fake->create()->content('Test content')->to(1)->send();

    $fake->assertPublished('Test content');
});

test('assertPublished matches partial content', function () {
    $fake = FakeSocialBu::fake();

    $fake->create()->content('Hello world, this is a test!')->to(1)->send();

    $fake->assertPublished('world');
});

test('assertPublishedCount passes with correct count', function () {
    $fake = FakeSocialBu::fake();

    $fake->create()->content('Post 1')->to(1)->send();
    $fake->create()->content('Post 2')->to(1)->send();

    $fake->assertPublishedCount(2);
});

test('assertPublishedTo passes when accounts match', function () {
    $fake = FakeSocialBu::fake();

    $fake->create()->content('Test')->to(1, 2, 3)->send();

    $fake->assertPublishedTo([1, 2, 3]);
});

test('assertNothingPublished passes when no posts', function () {
    $fake = FakeSocialBu::fake();

    $fake->assertNothingPublished();
});

test('throwOnPublish throws on next publish', function () {
    $fake = FakeSocialBu::fake();

    $fake->throwOnPublish(new SocialBuException('Simulated error'));

    $fake->create()->content('Test')->to(1)->send();
})->throws(SocialBuException::class, 'Simulated error');

test('throwOnPublish only affects next publish', function () {
    $fake = FakeSocialBu::fake();

    $fake->throwOnPublish(new SocialBuException('Simulated error'));

    try {
        $fake->create()->content('First')->to(1)->send();
    } catch (SocialBuException) {
        // Expected
    }

    // Second publish should work
    $post = $fake->create()->content('Second')->to(1)->send();

    expect($post->content)->toBe('Second');
});

test('fake records uploads', function () {
    $fake = FakeSocialBu::fake();

    $fake->media()->upload('/path/to/image.jpg');

    expect($fake->getUploads())->toHaveCount(1);
    expect($fake->getUploads()[0]['path'])->toBe('/path/to/image.jpg');
});

test('assertUploaded passes when file was uploaded', function () {
    $fake = FakeSocialBu::fake();

    $fake->media()->upload('/path/to/file.jpg');

    $fake->assertUploaded('/path/to/file.jpg');
});

test('assertUploadedCount passes with correct count', function () {
    $fake = FakeSocialBu::fake();

    $fake->media()->upload('/path/1.jpg');
    $fake->media()->upload('/path/2.jpg');

    $fake->assertUploadedCount(2);
});

test('withAccounts sets fake accounts', function () {
    $fake = FakeSocialBu::fake()
        ->withAccounts([
            ['id' => 1, 'name' => 'Test Account', 'type' => 'facebook'],
        ]);

    $accounts = $fake->accounts()->list();

    expect($accounts)->toHaveCount(1);
    expect($accounts[0])->toBeInstanceOf(Account::class);
    expect($accounts[0]->name)->toBe('Test Account');
});

test('withPosts sets fake posts', function () {
    $fake = FakeSocialBu::fake()
        ->withPosts([
            ['id' => 100, 'content' => 'Fake post', 'created_at' => '2025-01-01 12:00:00'],
        ]);

    $posts = $fake->posts()->list();

    expect($posts)->toHaveCount(1);
    expect($posts[0])->toBeInstanceOf(Post::class);
    expect($posts[0]->content)->toBe('Fake post');
});

test('isConfigured returns true', function () {
    $fake = FakeSocialBu::fake();

    expect($fake->isConfigured())->toBeTrue();
});

test('getAccountIds returns default ids', function () {
    $fake = FakeSocialBu::fake();

    expect($fake->getAccountIds())->toBe([1, 2, 3]);
});

test('create returns PostBuilder', function () {
    $fake = FakeSocialBu::fake();

    $builder = $fake->create();

    expect($builder)->toBeInstanceOf(\Hei\SocialBu\Builders\PostBuilder::class);
});

test('published posts get sequential ids', function () {
    $fake = FakeSocialBu::fake();

    $post1 = $fake->create()->content('Post 1')->to(1)->send();
    $post2 = $fake->create()->content('Post 2')->to(1)->send();

    expect($post1->id)->toBe(1);
    expect($post2->id)->toBe(2);
});

test('uploads get sequential ids', function () {
    $fake = FakeSocialBu::fake();

    $upload1 = $fake->media()->upload('/path/1.jpg');
    $upload2 = $fake->media()->upload('/path/2.jpg');

    expect($upload1->uploadToken)->toBe('fake-token-1');
    expect($upload2->uploadToken)->toBe('fake-token-2');
});

test('fake posts().update() returns true', function () {
    $fake = FakeSocialBu::fake();

    $result = $fake->posts()->update(1, ['content' => 'Updated']);

    expect($result)->toBeTrue();
});

test('fake posts().delete() returns true', function () {
    $fake = FakeSocialBu::fake();

    $result = $fake->posts()->delete(1);

    expect($result)->toBeTrue();
});

test('fake posts().all() returns fake posts', function () {
    $fake = FakeSocialBu::fake();
    $fake->withPosts([
        ['id' => 1, 'content' => 'Post 1', 'created_at' => '2025-01-01'],
        ['id' => 2, 'content' => 'Post 2', 'created_at' => '2025-01-01'],
    ]);

    $posts = $fake->posts()->all();

    expect($posts)->toHaveCount(2);
});

test('fake posts().paginate() returns PaginatedResponse', function () {
    $fake = FakeSocialBu::fake();
    $fake->withPosts([
        ['id' => 1, 'content' => 'Post 1', 'created_at' => '2025-01-01'],
    ]);

    $response = $fake->posts()->paginate();

    expect($response)->toBeInstanceOf(\Hei\SocialBu\Data\PaginatedResponse::class);
    expect($response->items)->toHaveCount(1);
    expect($response->currentPage)->toBe(1);
});

test('fake accounts().all() returns fake accounts', function () {
    $fake = FakeSocialBu::fake();
    $fake->withAccounts([
        ['id' => 1, 'name' => 'Account 1', 'type' => 'facebook'],
    ]);

    $accounts = $fake->accounts()->all();

    expect($accounts)->toHaveCount(1);
});

test('fake accounts().paginate() returns PaginatedResponse', function () {
    $fake = FakeSocialBu::fake();
    $fake->withAccounts([
        ['id' => 1, 'name' => 'Account 1', 'type' => 'facebook'],
    ]);

    $response = $fake->accounts()->paginate();

    expect($response)->toBeInstanceOf(\Hei\SocialBu\Data\PaginatedResponse::class);
    expect($response->items)->toHaveCount(1);
});

test('fake records options in published posts', function () {
    $fake = FakeSocialBu::fake();

    $fake->create()
        ->content('Reddit post')
        ->to(1)
        ->withOptions(['title' => 'My Title', 'subreddit' => 'laravel'])
        ->send();

    expect($fake->getPublished())->toHaveCount(1);
    expect($fake->getPublished()[0]['options'])->toBe(['title' => 'My Title', 'subreddit' => 'laravel']);
});

test('throwOnUpload throws on next upload', function () {
    $fake = FakeSocialBu::fake();

    $fake->throwOnUpload(new SocialBuException('Upload failed'));

    $fake->media()->upload('/path/to/file.jpg');
})->throws(SocialBuException::class, 'Upload failed');

test('throwOnUpload only affects next upload', function () {
    $fake = FakeSocialBu::fake();

    $fake->throwOnUpload(new SocialBuException('Upload failed'));

    try {
        $fake->media()->upload('/path/1.jpg');
    } catch (SocialBuException) {
        // Expected
    }

    // Second upload should work
    $upload = $fake->media()->upload('/path/2.jpg');

    expect($upload->uploadToken)->not->toBeEmpty();
});
