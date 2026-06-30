<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GamePublicId;
use App\Models\Player;
use App\Models\PlayerSubmission;
use App\Models\RewardCode;
use App\Models\SpinResult;
use App\Services\ClaimTransitionService;
use App\Services\SpinAllocationService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RuntimeGameController extends Controller
{
    public function __construct(
        protected SpinAllocationService $spinAllocationService,
        protected ClaimTransitionService $claimTransitionService,
    ) {
    }

    public function bootstrap(string $publicIdentifier): JsonResponse
    {
        $game = $this->resolveActiveGame($publicIdentifier);

        if (! $game) {
            return response()->json([
                'available' => false,
                'message' => 'Game is unavailable.',
            ], 404);
        }

        $publicId = $game->publicIds->firstWhere('is_primary', true) ?? $game->publicIds->first();
        $publishedConfig = $game->builderConfig?->published_config ?? [];
        $publishedGeneral = $publishedConfig['general'] ?? [];
        $publishedPresentation = $publishedConfig['presentation'] ?? [];
        $publishedDesign = $publishedConfig['design'] ?? [];

        return response()->json([
            'available' => true,
            'game' => [
                'name' => $publishedGeneral['name'] ?? $game->name,
                'slug' => $publishedGeneral['slug'] ?? $game->slug,
                'templateType' => $game->template_type?->value ?? $game->template_type,
                'status' => $game->status?->value ?? $game->status,
                'publicIdentifier' => $publicId?->public_id ?? $publicIdentifier,
            ],
            'theme' => array_merge($game->theme?->toArray() ?? [], [
                'wheel' => [
                    'palettePreset' => $publishedDesign['palette_preset'] ?? null,
                    'borderPreset' => $publishedDesign['border_preset'] ?? null,
                    'pointerPreset' => $publishedDesign['pointer_preset'] ?? null,
                    'centerLabel' => $publishedDesign['center_label'] ?? null,
                ],
            ]),
            'content' => ! empty($publishedPresentation)
                ? collect($publishedPresentation)
                    ->except(['fields', 'redirect'])
                    ->all()
                : $game->contentBlocks
                    ->sortBy('sort_order')
                    ->mapWithKeys(fn ($block) => [$block->block_key => $block->content_text ?: $block->content_payload]),
            'formFields' => $game->formFields
                ->where('is_active', true)
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($field) => [
                    'fieldKey' => $field->field_key,
                    'type' => $field->type,
                    'label' => $field->label,
                    'placeholder' => $field->placeholder,
                    'helpText' => $field->help_text,
                    'isRequired' => $field->is_required,
                    'options' => $field->options,
                ]),
            'prizes' => $game->prizes
                ->where('is_active', true)
                ->sortBy('sort_order')
                ->values()
                ->map(fn ($prize) => [
                    'code' => $prize->code,
                    'label' => $prize->label,
                    'description' => $prize->description,
                    'imageAssetPath' => $prize->image_asset_path,
                    'valueLabel' => $prize->value_label,
                ]),
            'rules' => [
                'requiresRewardCode' => $game->rules?->requires_reward_code ?? false,
                'maxSpinsPerPlayer' => $game->rules?->max_spins_per_player ?? 1,
                'redirectStrategy' => $game->rules?->redirect_strategy,
            ],
            'redirect' => optional($game->redirects->sortByDesc('is_primary')->first(), function ($redirect) {
                return [
                    'action' => $redirect->action,
                    'targetType' => $redirect->target_type,
                    'targetValue' => $redirect->target_value,
                    'fallbackValue' => $redirect->fallback_value,
                ];
            }),
        ]);
    }

    public function storeSubmission(Request $request, string $publicIdentifier): JsonResponse
    {
        $game = $this->resolveActiveGame($publicIdentifier);

        if (! $game) {
            return response()->json([
                'message' => 'Game is unavailable.',
            ], 404);
        }

        $payload = $request->input('payload', []);

        if (! is_array($payload)) {
            return response()->json([
                'message' => 'The payload must be an object.',
            ], 422);
        }

        $errors = $this->validateDynamicPayload($game->formFields->where('is_active', true), $payload);

        if ($errors !== []) {
            return response()->json([
                'message' => 'The submitted data is invalid.',
                'errors' => $errors,
            ], 422);
        }

        $player = Player::query()
            ->where('game_id', $game->id)
            ->when(
                filled($payload['phone'] ?? null),
                fn ($query) => $query->where('phone', $payload['phone'])
            )
            ->first();

        if (! $player) {
            $player = Player::create([
                'workspace_id' => $game->workspace_id,
                'game_id' => $game->id,
                'public_id' => (string) Str::uuid(),
                'full_name' => $payload['full_name'] ?? null,
                'phone' => $payload['phone'] ?? null,
                'email' => $payload['email'] ?? null,
                'status' => 'active',
            ]);
        } else {
            $player->update([
                'full_name' => $payload['full_name'] ?? $player->full_name,
                'phone' => $payload['phone'] ?? $player->phone,
                'email' => $payload['email'] ?? $player->email,
            ]);
        }

        $submission = PlayerSubmission::create([
            'workspace_id' => $game->workspace_id,
            'game_id' => $game->id,
            'player_id' => $player->id,
            'payload' => $payload,
            'source' => 'mini_app',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'playerPublicId' => $player->public_id,
            'submissionId' => $submission->id,
        ], 201);
    }

    public function checkEligibility(Request $request, string $publicIdentifier): JsonResponse
    {
        $game = $this->resolveActiveGame($publicIdentifier);

        if (! $game) {
            return response()->json([
                'eligible' => false,
                'reason' => 'game_unavailable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'player_public_id' => ['required', 'string'],
            'reward_code' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'eligible' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $player = Player::query()
            ->where('game_id', $game->id)
            ->where('public_id', $request->string('player_public_id'))
            ->first();

        if (! $player) {
            return response()->json([
                'eligible' => false,
                'reason' => 'player_not_found',
            ], 404);
        }

        $attemptCount = $player->spinAttempts()->count();
        $remainingSpins = max(0, ($game->rules?->max_spins_per_player ?? 1) - $attemptCount);

        if ($remainingSpins < 1) {
            return response()->json([
                'eligible' => false,
                'reason' => 'no_spins_remaining',
                'remainingSpins' => 0,
            ], 200);
        }

        $latestSubmission = PlayerSubmission::query()
            ->where('game_id', $game->id)
            ->where('player_id', $player->id)
            ->latest('submitted_at')
            ->first();

        $rewardCodeValue = $this->resolveRewardCodeValue(
            $game,
            $request->string('reward_code')->toString(),
            is_array($latestSubmission?->payload) ? $latestSubmission->payload : [],
        );
        $rewardCode = $this->resolveRewardCode($game->id, $rewardCodeValue);

        if (($game->rules?->requires_reward_code ?? false) && ! $rewardCode) {
            return response()->json([
                'eligible' => false,
                'reason' => 'reward_code_required',
                'remainingSpins' => $remainingSpins,
            ], 200);
        }

        if ($rewardCode && ($rewardCode->used_count >= $rewardCode->max_uses || $rewardCode->status->value !== 'active')) {
            return response()->json([
                'eligible' => false,
                'reason' => 'reward_code_unavailable',
                'remainingSpins' => $remainingSpins,
            ], 200);
        }

        return response()->json([
            'eligible' => true,
            'playerPublicId' => $player->public_id,
            'remainingSpins' => $remainingSpins,
        ]);
    }

    public function spin(Request $request, string $publicIdentifier): JsonResponse
    {
        $game = $this->resolveActiveGame($publicIdentifier);

        if (! $game) {
            return response()->json([
                'message' => 'Game is unavailable.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'player_public_id' => ['required', 'string'],
            'player_submission_id' => ['nullable', 'integer'],
            'reward_code' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The spin request is invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $player = Player::query()
            ->where('game_id', $game->id)
            ->where('public_id', $request->string('player_public_id'))
            ->firstOrFail();

        $submission = PlayerSubmission::query()
            ->where('game_id', $game->id)
            ->when(
                $request->filled('player_submission_id'),
                fn ($query) => $query->whereKey($request->integer('player_submission_id'))
            )
            ->latest('submitted_at')
            ->first();

        $rewardCodeValue = $this->resolveRewardCodeValue(
            $game,
            $request->string('reward_code')->toString(),
            is_array($submission?->payload) ? $submission->payload : [],
        );
        $rewardCode = $this->resolveRewardCode($game->id, $rewardCodeValue);

        try {
            $result = $this->spinAllocationService->allocate(
                $game,
                $player,
                $submission,
                $rewardCode,
                $request->string('idempotency_key')->toString() ?: null,
            );
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'spinResultId' => $result->id,
            'resultType' => $result->result_type,
            'claimStatus' => $result->claim_status?->value ?? $result->claim_status,
            'prize' => $result->prize ? [
                'code' => $result->prize->code,
                'label' => $result->prize->label,
                'description' => $result->prize->description,
            ] : null,
            'awardedPayload' => $result->awarded_payload,
        ]);
    }

    public function claim(Request $request, string $publicIdentifier): JsonResponse
    {
        $game = $this->resolveActiveGame($publicIdentifier);

        if (! $game) {
            return response()->json([
                'message' => 'Game is unavailable.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'spin_result_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The claim request is invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $spinResult = SpinResult::query()
            ->where('game_id', $game->id)
            ->whereKey($request->integer('spin_result_id'))
            ->firstOrFail();

        $claim = $this->claimTransitionService->claim($spinResult);

        return response()->json([
            'claimId' => $claim->id,
            'status' => $claim->status?->value ?? $claim->status,
            'action' => $claim->claim_action,
            'redirectTarget' => $claim->redirect_target,
            'metadata' => $claim->metadata,
        ]);
    }

    protected function resolveActiveGame(string $publicIdentifier): ?Game
    {
        $gamePublicId = GamePublicId::query()
            ->with([
                'game.workspace',
                'game.theme',
                'game.contentBlocks',
                'game.formFields',
                'game.rules',
                'game.redirects',
                'game.prizes',
                'game.builderConfig',
            ])
            ->where('is_active', true)
            ->where(function ($query) use ($publicIdentifier) {
                $query
                    ->where('public_id', $publicIdentifier)
                    ->orWhere('slug', $publicIdentifier);
            })
            ->first();

        $game = $gamePublicId?->game;

        if (! $game || ($game->status?->value ?? $game->status) !== 'active') {
            return null;
        }

        if ($game->builderConfig && $game->builderConfig->publication_status !== 'published') {
            return null;
        }

        if ($game->starts_at && now()->lt($game->starts_at)) {
            return null;
        }

        if ($game->ends_at && now()->gt($game->ends_at)) {
            return null;
        }

        return $game;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function validateDynamicPayload(Collection $fields, array $payload): array
    {
        $errors = [];

        foreach ($fields as $field) {
            $value = $payload[$field->field_key] ?? null;

            if ($field->is_required && blank($value)) {
                $errors[$field->field_key][] = 'This field is required.';
                continue;
            }

            if ($field->type === 'select' && filled($value) && is_array($field->options) && ! in_array($value, $field->options, true)) {
                $errors[$field->field_key][] = 'The selected value is invalid.';
            }
        }

        return $errors;
    }

    protected function resolveRewardCode(int $gameId, ?string $rewardCode): ?RewardCode
    {
        if (! filled($rewardCode)) {
            return null;
        }

        return RewardCode::query()
            ->where('game_id', $gameId)
            ->where('code', $rewardCode)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveRewardCodeValue(Game $game, ?string $explicitRewardCode, array $payload = []): ?string
    {
        $explicitRewardCode = filled($explicitRewardCode) ? trim((string) $explicitRewardCode) : null;

        if ($explicitRewardCode) {
            return $explicitRewardCode;
        }

        $rewardCodeField = $game->formFields
            ->where('is_active', true)
            ->first(function ($field) {
                $candidates = [
                    $field->field_key,
                    $field->label,
                    $field->placeholder,
                    $field->help_text,
                ];

                foreach ($candidates as $candidate) {
                    $normalizedCandidate = $this->normalizeLookupText($candidate);

                    if (
                        str_contains($normalizedCandidate, 'reward_code') ||
                        str_contains($normalizedCandidate, 'ma_du_thuong') ||
                        str_contains($normalizedCandidate, 'voucher_code')
                    ) {
                        return true;
                    }
                }

                return false;
            });

        if (! $rewardCodeField) {
            return null;
        }

        $value = $payload[$rewardCodeField->field_key] ?? null;

        return filled($value) ? trim((string) $value) : null;
    }

    protected function normalizeLookupText(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return '';
        }

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return str($transliterated !== false ? $transliterated : $value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();
    }
}
