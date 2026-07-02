<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameBuilderConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GameBuilderService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function palettePresets(): array
    {
        return [
            ['id' => 'sunrise', 'label' => 'Sunrise', 'colors' => ['#fdf1d0', '#ffcf64', '#ff914d', '#ff5040']],
            ['id' => 'marine', 'label' => 'Marine', 'colors' => ['#edf5ff', '#1e63a4', '#114d86', '#0c355e']],
            ['id' => 'soft-pop', 'label' => 'Soft Pop', 'colors' => ['#ffd8bf', '#f7d9cd', '#a8a0f1', '#7b58e5']],
            ['id' => 'mint', 'label' => 'Mint', 'colors' => ['#f5d0b5', '#f5f1ee', '#abd8d1', '#76a8a9']],
            ['id' => 'candy', 'label' => 'Candy', 'colors' => ['#dbdbdb', '#f6d1c5', '#f2a7b8', '#f98d9b']],
            ['id' => 'neon', 'label' => 'Neon', 'colors' => ['#20b9ad', '#b2dee7', '#f7f8fc', '#ffb869']],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function borderPresets(): array
    {
        return [
            ['id' => 'pink-star', 'label' => 'Pink Star', 'asset' => 'bg-vongquay/v4.png'],
            ['id' => 'classic-red', 'label' => 'Classic Red', 'asset' => 'bg-vongquay/v2.png'],
            ['id' => 'gold-ring', 'label' => 'Gold Ring', 'asset' => 'bg-vongquay/v3.png'],
            ['id' => 'violet-glow', 'label' => 'Violet Glow', 'asset' => 'bg-vongquay/v1.png'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function pointerPresets(): array
    {
        return [
            ['id' => 'teardrop-gold', 'label' => 'Teardrop Gold'],
            ['id' => 'triangle-fire', 'label' => 'Triangle Fire'],
            ['id' => 'diamond-soft', 'label' => 'Diamond Soft'],
        ];
    }

    public function ensureConfig(Game $game): GameBuilderConfig
    {
        $game->loadMissing([
            'theme',
            'contentBlocks',
            'formFields',
            'rules',
            'redirects',
            'prizes',
            'publicIds',
            'builderConfig',
        ]);

        if ($game->builderConfig) {
            return $game->builderConfig;
        }

        $snapshot = $this->snapshotFromGame($game);

        return $game->builderConfig()->create([
            'active_step' => 'general',
            'publication_status' => $game->published_at ? 'published' : 'draft',
            'draft_config' => $snapshot,
            'published_config' => $game->published_at ? $snapshot : null,
            'last_saved_at' => now(),
            'published_at' => $game->published_at,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshotFromGame(Game $game): array
    {
        $content = $game->contentBlocks
            ->sortBy('sort_order')
            ->mapWithKeys(fn ($block) => [$block->block_key => $block->content_text ?? ''])
            ->all();
        $themeTokens = $game->theme?->theme_tokens ?? [];

        return [
            'general' => [
                'name' => $game->name,
                'slug' => $game->publicIds->first()?->slug ?? $game->slug,
                'status' => $game->status?->value ?? (string) $game->status,
                'description' => $game->description,
            ],
            'rewards' => [
                'requires_reward_code' => (bool) $game->rules?->requires_reward_code,
                'max_spins_per_player' => $game->rules?->max_spins_per_player ?? 1,
                'prizes' => $game->prizes
                    ->sortBy('sort_order')
                    ->map(fn ($prize) => [
                        'id' => $prize->id,
                        'code' => $prize->code,
                        'label' => $prize->label,
                        'description' => $prize->description,
                        'quota' => $prize->quota,
                        'weight' => $prize->weight,
                        'is_active' => $prize->is_active,
                        'sort_order' => $prize->sort_order,
                    ])
                    ->values()
                    ->all(),
            ],
            'design' => [
                'primary_color' => $game->theme?->primary_color ?? '#f9c667',
                'secondary_color' => $game->theme?->secondary_color ?? '#fff8e4',
                'accent_color' => $game->theme?->accent_color ?? '#d79e2f',
                'palette_preset' => Arr::get($themeTokens, 'wheel.palette_preset', 'mint'),
                'border_preset' => Arr::get($themeTokens, 'wheel.border_preset', 'pink-star'),
                'border_asset_path' => Arr::get($themeTokens, 'wheel.border_asset_path'),
                'pointer_preset' => Arr::get($themeTokens, 'wheel.pointer_preset', 'teardrop-gold'),
                'center_label' => Arr::get($themeTokens, 'wheel.center_label', '19T'),
                'background_style' => $game->theme?->background_style ?? 'warm_gradient',
                'background_asset_path' => $game->theme?->background_asset_path,
                'preview_note' => Arr::get($themeTokens, 'wheel.preview_note', 'Quay ngay'),
            ],
            'presentation' => [
                'title' => $content['title'] ?? '',
                'subtitle' => $content['subtitle'] ?? '',
                'description' => $content['description'] ?? '',
                'spin_button' => $content['spin_button'] ?? 'Quay ngay',
                'continue_button' => $content['continue_button'] ?? 'Tiep tuc',
                'loading_message' => $content['loading_message'] ?? 'Dang tai...',
                'redirect' => [
                    'action' => $game->redirects->sortByDesc('is_primary')->first()?->action ?? 'open_oa',
                    'target_type' => $game->redirects->sortByDesc('is_primary')->first()?->target_type,
                    'target_value' => $game->redirects->sortByDesc('is_primary')->first()?->target_value,
                    'fallback_value' => $game->redirects->sortByDesc('is_primary')->first()?->fallback_value,
                    'message_template' => $game->redirects->sortByDesc('is_primary')->first()?->message_template,
                ],
                'fields' => $game->formFields
                    ->sortBy('sort_order')
                    ->map(fn ($field) => [
                        'id' => $field->id,
                        'field_key' => $field->field_key,
                        'type' => $field->type,
                        'label' => $field->label,
                        'placeholder' => $field->placeholder,
                        'help_text' => $field->help_text,
                        'is_required' => $field->is_required,
                        'is_active' => $field->is_active,
                        'options' => $field->options ?? [],
                        'sort_order' => $field->sort_order,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function saveDraft(GameBuilderConfig $config, string $step, array $payload): GameBuilderConfig
    {
        $draft = $config->draft_config ?? [];
        $draft[$this->draftSectionKey($step)] = $payload;

        $config->update([
            'active_step' => $step,
            'draft_config' => $draft,
            'last_saved_at' => now(),
        ]);

        return $config->fresh();
    }

    public function publish(Game $game, GameBuilderConfig $config): GameBuilderConfig
    {
        $draft = $this->normalizeDraftForPublication($config->draft_config ?? []);
        $this->assertPublishReady($draft);

        DB::transaction(function () use ($game, $config, $draft) {
            $this->syncDraftToRuntime($game, $draft);

            $config->update([
                'publication_status' => 'published',
                'draft_config' => $draft,
                'published_config' => $draft,
                'published_at' => now(),
                'last_saved_at' => now(),
            ]);

            $game->update([
                'published_at' => now(),
            ]);
        });

        $launchLinkService = app(GameLaunchLinkService::class);
        $relations = ['publicIds'];

        if ($launchLinkService->tableExists()) {
            $relations[] = 'launchLinks';
        }

        $launchLinkService->syncPublishedLinks($game->fresh($relations));

        return $config->fresh();
    }

    public function syncCurrentDraftToDatabase(Game $game, GameBuilderConfig $config): GameBuilderConfig
    {
        $draft = $config->draft_config ?? [];

        if ($config->publication_status === 'published') {
            $draft = $this->normalizeDraftForPublication($draft);
        }

        DB::transaction(function () use ($game, $config, $draft) {
            $this->syncDraftToRuntime($game, $draft);

            $attributes = [
                'draft_config' => $draft,
                'last_saved_at' => now(),
            ];

            if ($config->publication_status === 'published') {
                $attributes['published_config'] = $draft;
                $attributes['published_at'] = $game->published_at ?? now();
            }

            $config->update($attributes);
        });

        return $config->fresh();
    }

    public function unpublish(Game $game, GameBuilderConfig $config): GameBuilderConfig
    {
        $draft = $this->normalizeDraftForDraftState($config->draft_config ?? []);

        $this->syncDraftToRuntime($game, $draft);

        $config->update([
            'draft_config' => $draft,
            'publication_status' => 'draft',
            'published_config' => null,
            'published_at' => null,
        ]);

        $game->update([
            'published_at' => null,
        ]);

        return $config->fresh();
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    public function assertPublishReady(array $draft): void
    {
        $activePrizes = collect(data_get($draft, 'rewards.prizes', []))
            ->filter(fn ($prize) => (bool) data_get($prize, 'is_active', false));

        if (blank(data_get($draft, 'general.name')) || blank(data_get($draft, 'general.slug'))) {
            throw new \DomainException('General configuration is incomplete.');
        }

        if ($activePrizes->isEmpty()) {
            throw new \DomainException('At least one active prize is required before publishing.');
        }

        if (blank(data_get($draft, 'presentation.title')) || blank(data_get($draft, 'presentation.subtitle'))) {
            throw new \DomainException('Presentation content is incomplete.');
        }
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    protected function syncDraftToRuntime(Game $game, array $draft): void
    {
        $general = $draft['general'] ?? [];
        $rewards = $draft['rewards'] ?? [];
        $design = $draft['design'] ?? [];
        $presentation = $draft['presentation'] ?? [];

        $game->update([
            'name' => data_get($general, 'name', $game->name),
            'slug' => data_get($general, 'slug', $game->slug),
            'status' => data_get($general, 'status', $game->status?->value ?? 'active'),
            'description' => data_get($general, 'description', $game->description),
        ]);

        $game->publicIds()->where('is_primary', true)->update([
            'slug' => data_get($general, 'slug', $game->publicIds->first()?->slug ?? $game->slug),
        ]);

        $game->theme()->updateOrCreate(
            ['game_id' => $game->id],
            [
                'primary_color' => data_get($design, 'primary_color', '#f9c667'),
                'secondary_color' => data_get($design, 'secondary_color', '#fff8e4'),
                'accent_color' => data_get($design, 'accent_color', '#d79e2f'),
                'background_style' => data_get($design, 'background_style', 'warm_gradient'),
                'background_asset_path' => data_get($design, 'background_asset_path'),
                'theme_tokens' => [
                    'button_color' => data_get($design, 'primary_color', '#f9c667'),
                    'text_color' => '#6f4910',
                    'wheel' => [
                        'palette_preset' => data_get($design, 'palette_preset', 'mint'),
                        'border_preset' => data_get($design, 'border_preset', 'pink-star'),
                        'border_asset_path' => data_get($design, 'border_asset_path'),
                        'pointer_preset' => data_get($design, 'pointer_preset', 'teardrop-gold'),
                        'center_label' => data_get($design, 'center_label', '19T'),
                        'preview_note' => data_get($design, 'preview_note', 'Quay ngay'),
                    ],
                ],
            ],
        );

        $contentPayload = [
            'title' => data_get($presentation, 'title', 'Yeu Thuong'),
            'subtitle' => data_get($presentation, 'subtitle', 'Uong an lanh, gop ngan'),
            'description' => data_get($presentation, 'description', ''),
            'spin_button' => data_get($presentation, 'spin_button', 'Quay ngay'),
            'continue_button' => data_get($presentation, 'continue_button', 'Tiep tuc'),
            'loading_message' => data_get($presentation, 'loading_message', 'Dang tai...'),
        ];

        foreach ($contentPayload as $blockKey => $value) {
            $game->contentBlocks()->updateOrCreate(
                ['block_key' => $blockKey],
                [
                    'label' => Str::of($blockKey)->replace('_', ' ')->title()->value(),
                    'content_text' => $value,
                    'sort_order' => array_search($blockKey, array_keys($contentPayload), true) + 1,
                ],
            );
        }

        $game->rules()->updateOrCreate(
            ['game_id' => $game->id],
            [
                'requires_reward_code' => (bool) data_get($rewards, 'requires_reward_code', false),
                'max_spins_per_player' => (int) data_get($rewards, 'max_spins_per_player', 1),
            ],
        );

        $redirect = data_get($presentation, 'redirect', []);
        $game->redirects()->updateOrCreate(
            ['game_id' => $game->id, 'is_primary' => true],
            [
                'action' => data_get($redirect, 'action', 'open_oa'),
                'target_type' => data_get($redirect, 'target_type'),
                'target_value' => data_get($redirect, 'target_value'),
                'fallback_value' => data_get($redirect, 'fallback_value'),
                'message_template' => data_get($redirect, 'message_template'),
            ],
        );

        $retainedFieldIds = [];

        collect(data_get($presentation, 'fields', []))
            ->each(function (array $fieldPayload, int $index) use ($game, &$retainedFieldIds) {
                $fieldKey = Str::snake((string) data_get($fieldPayload, 'field_key', 'field_'.$index));
                $field = isset($fieldPayload['id'])
                    ? $game->formFields()->whereKey($fieldPayload['id'])->first()
                    : null;

                if (! $field) {
                    $field = $game->formFields()
                        ->where('field_key', $fieldKey)
                        ->first();
                }

                $attributes = [
                    'field_key' => $fieldKey,
                    'type' => data_get($fieldPayload, 'type', 'text'),
                    'label' => data_get($fieldPayload, 'label', 'Field '.($index + 1)),
                    'placeholder' => data_get($fieldPayload, 'placeholder'),
                    'help_text' => data_get($fieldPayload, 'help_text'),
                    'is_required' => (bool) data_get($fieldPayload, 'is_required', false),
                    'is_active' => (bool) data_get($fieldPayload, 'is_active', true),
                    'options' => data_get($fieldPayload, 'type') === 'select'
                        ? array_values(array_filter(data_get($fieldPayload, 'options', [])))
                        : null,
                    'validation_rules' => (bool) data_get($fieldPayload, 'is_required', false) ? ['required'] : ['nullable'],
                    'sort_order' => $index + 1,
                ];

                if (! $field) {
                    $field = $game->formFields()->create($attributes);
                } else {
                    $field->update($attributes);
                }

                $retainedFieldIds[] = $field->id;
            });

        $game->formFields()
            ->when($retainedFieldIds !== [], fn ($query) => $query->whereNotIn('id', $retainedFieldIds))
            ->when($retainedFieldIds === [], fn ($query) => $query)
            ->delete();

        collect(data_get($rewards, 'prizes', []))
            ->each(function (array $prizePayload, int $index) use ($game) {
                $code = $this->normalizePrizeCode(data_get($prizePayload, 'code'), $index);
                $label = $this->normalizePrizeLabel(data_get($prizePayload, 'label'), $code, $index);
                $prize = isset($prizePayload['id'])
                    ? $game->prizes()->whereKey($prizePayload['id'])->first()
                    : null;

                if (! $prize) {
                    $prize = $game->prizes()
                        ->where('code', $code)
                        ->first();
                }

                if (! $prize) {
                    $prize = $game->prizes()->create([
                        'code' => $code,
                        'label' => $label,
                        'inventory_type' => 'quota',
                        'metadata' => [],
                    ]);
                }

                $prize->update([
                    'code' => $code,
                    'label' => $label,
                    'description' => data_get($prizePayload, 'description'),
                    'quota' => data_get($prizePayload, 'quota'),
                    'weight' => (int) data_get($prizePayload, 'weight', 0),
                    'is_active' => (bool) data_get($prizePayload, 'is_active', true),
                    'sort_order' => $index + 1,
                ]);
            });
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function mergedPreviewConfig(array $config): array
    {
        $palette = collect($this->palettePresets())
            ->firstWhere('id', data_get($config, 'design.palette_preset', 'mint'));

        $sliceColors = $palette['colors'] ?? ['#fdf1d0', '#ffcf64', '#ff914d', '#ff5040'];

        return array_merge($config, [
            'preview' => [
                'slice_colors' => $sliceColors,
                'border_preset' => data_get($config, 'design.border_preset', 'pink-star'),
                'border_asset_path' => data_get($config, 'design.border_asset_path'),
                'pointer_preset' => data_get($config, 'design.pointer_preset', 'teardrop-gold'),
                'background_style' => data_get($config, 'design.background_style', 'warm_gradient'),
                'background_asset_path' => data_get($config, 'design.background_asset_path'),
            ],
        ]);
    }

    protected function draftSectionKey(string $step): string
    {
        return $step === 'publish' ? 'presentation' : $step;
    }

    protected function normalizePrizeCode(mixed $code, int $index): string
    {
        $value = trim((string) ($code ?? ''));

        if ($value !== '') {
            return $value;
        }

        return 'prize_'.($index + 1).'_'.Str::lower(Str::random(5));
    }

    protected function normalizePrizeLabel(mixed $label, string $code, int $index): string
    {
        $value = trim((string) ($label ?? ''));

        if ($value !== '') {
            return $value;
        }

        $humanizedCode = trim(Str::of($code)->replace(['-', '_'], ' ')->squish()->title()->value());

        if ($humanizedCode !== '') {
            return $humanizedCode;
        }

        return 'Phần thưởng '.($index + 1);
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    protected function normalizeDraftForPublication(array $draft): array
    {
        $status = data_get($draft, 'general.status');

        if ($status === null || $status === '' || $status === GameStatus::Draft->value) {
            data_set($draft, 'general.status', GameStatus::Active->value);
        }

        return $draft;
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    protected function normalizeDraftForDraftState(array $draft): array
    {
        data_set($draft, 'general.status', GameStatus::Draft->value);

        return $draft;
    }
}
