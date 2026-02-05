<?php

declare(strict_types=1);

namespace Hei\SocialBu\Commands;

use Hei\SocialBu\Client\SocialBuClientInterface;
use Hei\SocialBu\Exceptions\SocialBuException;
use Hei\SocialBu\Exceptions\ValidationException;
use Illuminate\Console\Command;

class TestPostCommand extends Command
{
    protected $signature = 'socialbu:test
                            {content : The post content}
                            {--media= : Path to media file}
                            {--schedule= : Schedule for a future time (Y-m-d H:i:s)}
                            {--to=* : Account IDs to post to}
                            {--dry-run : Validate without posting}';

    protected $description = 'Send a test post to SocialBu';

    public function handle(SocialBuClientInterface $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('SocialBu is not configured. Set SOCIALBU_TOKEN in your .env file.');

            return self::FAILURE;
        }

        $builder = $client->create()
            ->content($this->argument('content'));

        if ($media = $this->option('media')) {
            if (! file_exists($media)) {
                $this->error("Media file not found: {$media}");

                return self::FAILURE;
            }
            $builder->media($media);
        }

        if ($schedule = $this->option('schedule')) {
            $builder->scheduledAt($schedule);
        }

        $accountIds = $this->option('to');
        if (! empty($accountIds)) {
            $builder->to(array_map('intval', $accountIds));
        }

        try {
            if ($this->option('dry-run')) {
                $payload = $builder->dryRun();
                $this->info('Dry run - payload that would be sent:');
                $this->line(json_encode($payload, JSON_PRETTY_PRINT));

                return self::SUCCESS;
            }

            $this->info('Sending post...');

            $post = $builder->send();

            $this->newLine();
            $this->info('Post created successfully!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $post->id],
                    ['Status', $post->status],
                    ['Content', substr($post->content, 0, 50).(strlen($post->content) > 50 ? '...' : '')],
                    ['Accounts', implode(', ', $post->accountIds)],
                    ['Scheduled', $post->publishAt?->toDateTimeString() ?? 'Immediate'],
                ]
            );

            return self::SUCCESS;
        } catch (ValidationException $e) {
            $this->error('Validation failed:');
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->line("  - {$field}: {$message}");
                }
            }

            return self::FAILURE;
        } catch (SocialBuException $e) {
            $this->error('Failed to create post: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
