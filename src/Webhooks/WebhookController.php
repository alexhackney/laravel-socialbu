<?php

declare(strict_types=1);

namespace Hei\SocialBu\Webhooks;

use Hei\SocialBu\Events\AccountStatusChanged;
use Hei\SocialBu\Events\PostStatusChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    /**
     * Handle post status webhook.
     */
    public function handlePost(Request $request): JsonResponse
    {
        $payload = WebhookPayload::fromArray($request->all());

        $postId = $payload->getPostId();
        $accountId = $payload->getAccountId();
        $status = $payload->getStatus();

        if ($postId === null || $accountId === null || $status === null) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        event(new PostStatusChanged(
            postId: $postId,
            accountId: $accountId,
            status: $status,
            payload: $payload->data,
        ));

        return response()->json(['received' => true]);
    }

    /**
     * Handle account status webhook.
     */
    public function handleAccount(Request $request): JsonResponse
    {
        $payload = WebhookPayload::fromArray($request->all());

        $accountId = $payload->getAccountId();
        $action = $payload->getAction();
        $accountType = $payload->data['type'] ?? $payload->data['platform'] ?? 'unknown';
        $accountName = $payload->data['name'] ?? '';

        if ($accountId === null || $action === null) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        event(new AccountStatusChanged(
            accountId: $accountId,
            accountType: $accountType,
            accountName: $accountName,
            action: $action,
            payload: $payload->data,
        ));

        return response()->json(['received' => true]);
    }
}
