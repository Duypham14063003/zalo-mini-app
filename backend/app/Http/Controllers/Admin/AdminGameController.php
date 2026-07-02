<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClaimStatus;
use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Game;
use App\Models\PlayerSubmission;
use App\Models\RewardCode;
use App\Models\SpinResult;
use App\Services\GameBuilderService;
use App\Services\GameLaunchLinkService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminGameController extends Controller
{
    public function __construct(
        protected GameBuilderService $gameBuilderService,
        protected GameLaunchLinkService $gameLaunchLinkService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $games = Game::query()
            ->with(['workspace', 'theme', 'rules', 'publicIds', 'builderConfig'])
            ->withCount(['players', 'prizes', 'rewardCodes'])
            ->when(
                ! $user->isPlatformAdmin(),
                fn ($query) => $query->whereIn('workspace_id', $user->managedWorkspaceIds())
            )
            ->latest('updated_at')
            ->get();

        return view('games.index', [
            'games' => $games,
        ]);
    }

    public function edit(Request $request, Game $game): View
    {
        $this->authorize('view', $game);

        $relations = [
            'workspace',
            'theme',
            'rules',
            'redirects',
            'publicIds',
            'contentBlocks' => fn ($query) => $query->orderBy('sort_order'),
            'formFields' => fn ($query) => $query->orderBy('sort_order'),
            'prizes' => fn ($query) => $query->orderBy('sort_order'),
            'builderConfig',
        ];

        if ($this->gameLaunchLinkService->tableExists()) {
            $relations[] = 'launchLinks';
        }

        $game->load($relations);
        $game->loadCount([
            'players',
            'prizes',
            'spinResults as winning_results_count' => fn (Builder $query) => $query->where('result_type', 'prize'),
            'spinResults as winning_players_count' => fn (Builder $query) => $query
                ->select(DB::raw('count(distinct player_id)'))
                ->where('result_type', 'prize'),
        ]);

        $builderConfig = $this->gameBuilderService->ensureConfig($game);
        $step = $this->normaliseStep($request->query('step', $builderConfig->active_step));
        $draftConfig = $builderConfig->draft_config ?? [];

        return view('games.edit', [
            'game' => $game,
            'step' => $step,
            'builderConfig' => $builderConfig,
            'draftConfig' => $draftConfig,
            'previewConfig' => $this->gameBuilderService->mergedPreviewConfig($draftConfig),
            'statusOptions' => collect(GameStatus::cases())->map(fn (GameStatus $status) => $status->value)->all(),
            'palettePresets' => $this->gameBuilderService->palettePresets(),
            'borderPresets' => $this->gameBuilderService->borderPresets(),
            'pointerPresets' => $this->gameBuilderService->pointerPresets(),
            'steps' => $this->steps(),
            'launchData' => $this->launchViewData($game),
        ]);
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        $this->authorize('view', $game);

        $builderConfig = $this->gameBuilderService->ensureConfig($game);
        $step = $this->normaliseStep($request->string('step')->toString() ?: $builderConfig->active_step);
        $intent = $request->string('intent')->toString() ?: 'save';

        try {
            if ($intent === 'publish') {
                $this->gameBuilderService->publish($game, $builderConfig);
                $summary = $this->gameLaunchLinkService->summarizeStatuses(
                    $game->fresh($this->gameLaunchLinkService->tableExists() ? ['launchLinks'] : [])
                );

                return redirect()
                    ->route('games.edit', ['game' => $game, 'step' => 'publish'])
                    ->with('status', $summary['invalid'] > 0
                        ? 'Game da duoc publish, nhung van con kenh launch chua san sang.'
                        : 'Game da duoc publish va link launch da san sang.');
            }

            if ($intent === 'unpublish') {
                $this->gameBuilderService->unpublish($game, $builderConfig);
                $this->gameLaunchLinkService->archiveLinks(
                    $game->fresh($this->gameLaunchLinkService->tableExists() ? ['launchLinks'] : [])
                );

                return redirect()
                    ->route('games.edit', ['game' => $game, 'step' => 'publish'])
                    ->with('status', 'Game da duoc chuyen ve draft.');
            }

            if ($intent === 'regenerate_launch') {
                $relations = ['publicIds'];

                if ($this->gameLaunchLinkService->tableExists()) {
                    $relations[] = 'launchLinks';
                }

                $this->gameLaunchLinkService->syncPublishedLinks($game->fresh($relations));

                return redirect()
                    ->route('games.edit', ['game' => $game, 'step' => 'publish'])
                    ->with('status', 'Da tao lai link launch cho game.');
            }

            $payload = $this->validateStepPayload($request, $step, $game);
            $builderConfig = $this->gameBuilderService->saveDraft($builderConfig, $step, $payload);

            $nextStep = $intent === 'continue' ? $this->nextStep($step) : $step;

            return redirect()
                ->route('games.edit', ['game' => $game, 'step' => $nextStep])
                ->with('status', 'Draft da duoc luu.');
        } catch (DomainException $exception) {
            return redirect()
                ->back()
                ->withInput()
                ->with('status', $exception->getMessage());
        }
    }

    public function rewardCodes(Request $request, Game $game): View
    {
        $this->authorize('view', $game);

        $status = $request->query('status');

        $rewardCodes = RewardCode::query()
            ->with(['prize', 'lastUsedByPlayer'])
            ->where('game_id', $game->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('games.reward-codes', [
            'game' => $game,
            'rewardCodes' => $rewardCodes,
            'statusFilter' => $status,
        ]);
    }

    public function storeRewardCodes(Request $request, Game $game): RedirectResponse
    {
        $this->authorize('view', $game);

        $validated = $request->validate([
            'codes' => ['required', 'string'],
            'max_uses' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $lines = collect(preg_split('/\r\n|\r|\n/', $validated['codes']))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->unique();

        $created = 0;

        foreach ($lines as $code) {
            if ($game->rewardCodes()->where('code', $code)->exists()) {
                continue;
            }

            $game->rewardCodes()->create([
                'code' => $code,
                'status' => 'active',
                'max_uses' => $validated['max_uses'] ?? 1,
                'used_count' => 0,
                'metadata' => [
                    'source' => 'admin_bulk_create',
                ],
            ]);

            $created++;
        }

        return redirect()
            ->route('games.reward-codes', $game)
            ->with('status', "Da them {$created} reward code.");
    }

    public function submissions(Request $request, Game $game): View
    {
        $this->authorize('view', $game);

        $keyword = $request->string('q')->toString();

        $submissions = PlayerSubmission::query()
            ->with(['player'])
            ->where('game_id', $game->id)
            ->when(
                filled($keyword),
                fn ($query) => $query->where(function ($inner) use ($keyword) {
                    $search = '%'.Str::lower($keyword).'%';

                    $inner->whereRaw('LOWER(CAST(payload AS TEXT)) LIKE ?', [$search]);
                })
            )
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('games.submissions', [
            'game' => $game,
            'submissions' => $submissions,
            'keyword' => $keyword,
        ]);
    }

    public function activity(Request $request, Game $game): View
    {
        $this->authorize('view', $game);

        $claimStatus = $request->query('claim_status');

        $spinResults = SpinResult::query()
            ->with(['player', 'prize', 'claim'])
            ->where('game_id', $game->id)
            ->when($claimStatus, fn ($query) => $query->where('claim_status', $claimStatus))
            ->latest('resolved_at')
            ->paginate(15)
            ->withQueryString();

        return view('games.activity', [
            'game' => $game,
            'spinResults' => $spinResults,
            'claimStatus' => $claimStatus,
        ]);
    }

    public function winners(Request $request, Game $game): View
    {
        $this->authorize('view', $game);

        $keyword = $request->string('q')->toString();
        $claimStatus = $request->query('claim_status');

        $winners = SpinResult::query()
            ->with(['player', 'prize', 'claim'])
            ->where('game_id', $game->id)
            ->where('result_type', 'prize')
            ->when($claimStatus, fn ($query) => $query->where('claim_status', $claimStatus))
            ->when(
                filled($keyword),
                fn ($query) => $query->whereHas('player', function ($playerQuery) use ($keyword) {
                    $search = '%'.Str::lower($keyword).'%';

                    $playerQuery->where(function ($inner) use ($search) {
                        $inner
                            ->whereRaw('LOWER(full_name) LIKE ?', [$search])
                            ->orWhereRaw('LOWER(phone) LIKE ?', [$search])
                            ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
                    });
                })
            )
            ->latest('resolved_at')
            ->paginate(15)
            ->withQueryString();

        return view('games.winners', [
            'game' => $game,
            'winners' => $winners,
            'keyword' => $keyword,
            'claimStatus' => $claimStatus,
        ]);
    }

    public function claims(Request $request, Game $game): View
    {
        $this->authorize('view', $game);

        $status = $request->query('status');

        $claims = Claim::query()
            ->with(['player', 'spinResult.prize'])
            ->where('game_id', $game->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('claimed_at')
            ->paginate(15)
            ->withQueryString();

        return view('games.claims', [
            'game' => $game,
            'claims' => $claims,
            'statusFilter' => $status,
        ]);
    }

    public function fulfillClaim(Game $game, Claim $claim): RedirectResponse
    {
        $this->authorize('view', $game);
        abort_unless($claim->game_id === $game->id, 404);

        $claim->update([
            'status' => ClaimStatus::Fulfilled,
            'fulfilled_at' => now(),
        ]);

        $claim->spinResult?->update([
            'claim_status' => ClaimStatus::Fulfilled,
        ]);

        return redirect()
            ->route('games.claims', $game)
            ->with('status', 'Claim da duoc danh dau fulfilled.');
    }

    /**
     * @return array<string, string>
     */
    protected function steps(): array
    {
        return [
            'general' => 'Cau hinh chung',
            'rewards' => 'Cau hinh phan thuong',
            'design' => 'Thiet ke vong quay',
            'publish' => 'Thiet ke game',
        ];
    }

    protected function nextStep(string $step): string
    {
        $steps = array_keys($this->steps());
        $index = array_search($step, $steps, true);

        if ($index === false || ! isset($steps[$index + 1])) {
            return $step;
        }

        return $steps[$index + 1];
    }

    protected function normaliseStep(string $step): string
    {
        return array_key_exists($step, $this->steps()) ? $step : 'general';
    }

    /**
     * @return array<string, string>
     */
    protected function launchViewData(Game $game): array
    {
        $relations = ['publicIds', 'builderConfig'];

        if ($this->gameLaunchLinkService->tableExists()) {
            $relations[] = 'launchLinks';
        }

        $game->loadMissing($relations);

        $launchLinks = $this->gameLaunchLinkService->tableExists() ? $game->launchLinks : collect();
        $webPreview = $launchLinks->firstWhere('channel', 'web_preview');
        $zaloMiniApp = $launchLinks->firstWhere('channel', 'zalo_mini_app');
        $summary = $this->gameLaunchLinkService->summarizeStatuses($game);
        $isPublished = (bool) $game->published_at
            && $game->builderConfig?->publication_status === 'published'
            && ($game->status?->value ?? $game->status) !== GameStatus::Draft->value;

        return [
            'public_id' => (string) ($game->publicIds()->where('is_primary', true)->value('public_id') ?: $game->publicIds()->value('public_id') ?: 'Chua co'),
            'status' => ! $this->gameLaunchLinkService->tableExists()
                ? 'Thieu bang game_launch_links'
                : (! $isPublished
                ? 'Game dang o ban nhap'
                : "{$summary['ready']} san sang / {$summary['invalid']} chua san sang"),
            'preview_url' => (string) ($webPreview->launch_url ?? ''),
            'miniapp_path' => (string) ($zaloMiniApp->miniapp_path ?? $webPreview->miniapp_path ?? ''),
            'zalo_url' => (string) ($zaloMiniApp->launch_url ?? ''),
            'qr_payload' => (string) ($zaloMiniApp->qr_payload ?? $webPreview->qr_payload ?? ''),
            'qr_preview_url' => $this->buildQrPreviewUrl((string) ($zaloMiniApp->qr_payload ?? $webPreview->qr_payload ?? '')),
            'message' => ! $this->gameLaunchLinkService->tableExists()
                ? 'Database chua co bang game_launch_links. Hay chay php artisan migrate de kich hoat tinh nang link trien khai.'
                : (! $isPublished
                ? 'Game chua duoc xuat ban cong khai. Khong nen chia se cac link ben duoi cho nguoi dung.'
                : ($zaloMiniApp->metadata['message'] ?? ($summary['invalid'] > 0
                    ? 'Van con kenh launch chua san sang.'
                    : 'Link launch da san sang de chia se.'))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateStepPayload(Request $request, string $step, Game $game): array
    {
        return match ($step) {
            'general' => $request->validate([
                'general.name' => ['required', 'string', 'max:255'],
                'general.slug' => ['required', 'string', 'max:255'],
                'general.status' => ['required', Rule::in(collect(GameStatus::cases())->map(fn (GameStatus $status) => $status->value)->all())],
                'general.description' => ['nullable', 'string'],
            ])['general'],
            'rewards' => [
                'requires_reward_code' => (bool) $request->boolean('rewards.requires_reward_code'),
                'max_spins_per_player' => (int) $request->validate([
                    'rewards.max_spins_per_player' => ['required', 'integer', 'min:1', 'max:10'],
                ])['rewards']['max_spins_per_player'],
                'prizes' => collect($request->input('rewards.prizes', []))
                    ->values()
                    ->map(function (array $prize) {
                        return [
                            'id' => isset($prize['id']) ? (int) $prize['id'] : null,
                            'code' => $prize['code'] ?? null,
                            'label' => $prize['label'] ?? '',
                            'description' => $prize['description'] ?? null,
                            'quota' => filled($prize['quota'] ?? null) ? (int) $prize['quota'] : null,
                            'weight' => (int) ($prize['weight'] ?? 0),
                            'is_active' => (bool) ($prize['is_active'] ?? false),
                        ];
                    })
                    ->filter(fn ($prize) => filled($prize['label']) || filled($prize['code']))
                    ->values()
                    ->all(),
            ],
            'design' => $request->validate([
                'design.primary_color' => ['required', 'string', 'max:20'],
                'design.secondary_color' => ['required', 'string', 'max:20'],
                'design.accent_color' => ['required', 'string', 'max:20'],
                'design.palette_preset' => ['required', 'string', 'max:50'],
                'design.border_preset' => ['required', 'string', 'max:50'],
                'design.border_asset_path' => ['nullable', 'string', 'max:255'],
                'design.pointer_preset' => ['required', 'string', 'max:50'],
                'design.center_label' => ['required', 'string', 'max:20'],
                'design.background_style' => ['required', 'string', 'max:50'],
                'design.background_asset_path' => ['nullable', 'string', 'max:255'],
                'design.preview_note' => ['nullable', 'string', 'max:255'],
            ])['design'],
            'publish' => [
                'title' => $request->input('presentation.title'),
                'subtitle' => $request->input('presentation.subtitle'),
                'description' => $request->input('presentation.description'),
                'spin_button' => $request->input('presentation.spin_button'),
                'continue_button' => $request->input('presentation.continue_button'),
                'loading_message' => $request->input('presentation.loading_message'),
                'redirect' => [
                    'action' => $request->input('presentation.redirect.action'),
                    'target_type' => $request->input('presentation.redirect.target_type'),
                    'target_value' => $request->input('presentation.redirect.target_value'),
                    'fallback_value' => $request->input('presentation.redirect.fallback_value'),
                    'message_template' => $request->input('presentation.redirect.message_template'),
                ],
                'fields' => collect($request->input('presentation.fields', []))
                    ->values()
                    ->map(fn (array $field, int $index) => $this->normalisePresentationField($field, $index))
                    ->filter()
                    ->values()
                    ->all(),
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>|null
     */
    protected function normalisePresentationField(array $field, int $index): ?array
    {
        $hasExistingId = isset($field['id']) && $field['id'] !== '';
        $rawFieldKey = trim((string) ($field['field_key'] ?? ''));
        $label = trim((string) ($field['label'] ?? ''));
        $placeholder = trim((string) ($field['placeholder'] ?? ''));
        $helpText = trim((string) ($field['help_text'] ?? ''));
        $rawOptions = trim((string) ($field['options'] ?? ''));

        if ((bool) ($field['remove'] ?? false)) {
            return null;
        }

        if (! $hasExistingId && $rawFieldKey === '' && $label === '' && $placeholder === '' && $helpText === '' && $rawOptions === '') {
            return null;
        }

        $baseFieldKey = $rawFieldKey !== ''
            ? Str::snake($rawFieldKey)
            : Str::snake($label !== '' ? $label : 'custom_field_'.$index);

        $fieldKey = $rawFieldKey !== ''
            ? $baseFieldKey
            : $baseFieldKey.'_'.$index;

        $type = in_array(($field['type'] ?? 'text'), ['text', 'tel', 'select'], true)
            ? (string) $field['type']
            : 'text';

        $normalised = [
            'id' => $hasExistingId ? (int) $field['id'] : null,
            'field_key' => $fieldKey,
            'type' => $type,
            'label' => $label,
            'placeholder' => $placeholder !== '' ? $placeholder : null,
            'help_text' => $helpText !== '' ? $helpText : null,
            'is_required' => (bool) ($field['is_required'] ?? false),
            'is_active' => array_key_exists('is_active', $field) ? (bool) $field['is_active'] : true,
            'options' => $type === 'select' && $rawOptions !== ''
                ? collect(explode("\n", $rawOptions))->map(fn ($option) => trim($option))->filter()->values()->all()
                : [],
        ];

        return $normalised;
    }

    protected function buildQrPreviewUrl(string $payload): string
    {
        if ($payload === '') {
            return '';
        }

        return 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data='.rawurlencode($payload);
    }
}
