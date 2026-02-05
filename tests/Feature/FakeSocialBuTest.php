<?php

declare(strict_types=1);

use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Data\Account;
use Hei\SocialBu\Data\Post;
use Hei\SocialBu\Exceptions\PostCreationException;
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

    $fake->throwOnPublish(new PostCreationException('Simulated error'));

    $fake->create()->content('Test')->to(1)->send();
})->throws(PostCreationException::class, 'Simulated error');

test('throwOnPublish only affects next publish', function () {
    $fake = FakeSocialBu::fake();

    $fake->throwOnPublish(new PostCreationException('Simulated error'));

    try {
        $fake->create()->content('First')->to(1)->send();
    } catch (PostCreationException) {
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
