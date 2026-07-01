<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SpinService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpinController extends Controller
{
    public function __construct(
        protected SpinService $spinService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'userId' => ['required', 'string'],
        ]);

        try {
            return response()->json(
                $this->spinService->spin($payload['userId'])
            );
        } catch (DomainException $exception) {
            $message = $exception->getMessage();
            $status = $message === 'User not found.' ? 404 : 422;

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }
    }
}
