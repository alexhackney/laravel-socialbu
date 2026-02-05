<?php

declare(strict_types=1);

namespace Hei\SocialBu\Commands;

use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Exceptions\NotFoundException;
use Hei\SocialBu\Exceptions\SocialBuException;
use Illuminate\Console\Command;

class GetPostCommand extends Command
{
    protected $signature = 'socialbu:post
                            {id : The post ID}
                            {--json : Output as JSON}';

    protected $description = 'Get details of a specific post';

    public function handle(SocialBuClientInterface $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('SocialBu is not configured. Set SOCIALBU_TOKEN in your .env file.');

            return self::FAILURE;
        }

        $postId = (int) $this->argument('id');

        try {
            $post = $client->posts()->get($postId);

            if ($this->option('json')) {
                $this->line(json_encode($post->toArray(), JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }

            $this->info("Post #{$post->id}");
            $this->newLine();

            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $post->id],
                    ['Status', $post->status],
                    ['Content', $post->content],
                    ['Accounts', implode(', ', $post->accountIds)],
                    ['Scheduled', $post->publishAt?->toDateTimeString() ?? '-'],
                    ['Created', $post->createdAt->toDateTimeString()],
                    ['Updated', $post->updatedAt?->toDateTimeString() ?? '-'],
                    ['Attachments', $post->attachments ? count($post->attachments).' file(s)' : 'None'],
                ]
            );

            return self::SUCCESS;
        } catch (NotFoundException $e) {
            $this->error("Post #{$postId} not found.");

            return self::FAILURE;
        } catch (SocialBuException $e) {
            $this->error('Failed to fetch post: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
