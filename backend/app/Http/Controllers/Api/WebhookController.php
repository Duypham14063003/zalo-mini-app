<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebhookController extends Controller
{
    public function __construct(
        protected WebhookService $webhookService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'event_name' => ['required', 'string', Rule::in(['user_revoke_consent', 'user_delete_data'])],
            'user_id' => ['required', 'string'],
        ]);

        return response()->json(
            $this->webhookService->handle($payload['event_name'], $payload['user_id'])
        );
    }
}
