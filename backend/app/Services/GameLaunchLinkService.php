<?php

namespace App\Services;

use App\Enums\GameLaunchChannel;
use App\Enums\GameLaunchEntryType;
use App\Enums\GameLaunchStatus;
use App\Models\Game;
use App\Models\GameLaunchLink;
use App\Models\GamePublicId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GameLaunchLinkService
{
    public function tableExists(): bool
    {
        return Schema::hasTable('game_launch_links');
    }

    /**
     * @return Collection<int, GameLaunchLink>
     */
    public function syncPublishedLinks(Game $game): Collection
    {
        if (! $this->tableExists()) {
            return collect();
        }

        $game->loadMissing(['publicIds', 'launchLinks']);

        $primaryPublicId = $this->resolvePrimaryPublicId($game);
        $slug = $primaryPublicId->slug ?: $game->slug;

        $links = collect([
            $this->upsertWebPreviewLink($game, $primaryPublicId, $slug),
            $this->upsertZaloMiniAppLink($game, $primaryPublicId, $slug),
        ]);

        $game->unsetRelation('launchLinks');
        $game->load('launchLinks');

        return $links;
    }

    /**
     * @return array{ready:int,invalid:int,draft:int,archived:int,total:int}
     */
    public function summarizeStatuses(Game $game): array
    {
        if (! $this->tableExists()) {
            return [
                'ready' => 0,
                'invalid' => 0,
                'draft' => 0,
                'archived' => 0,
                'total' => 0,
            ];
        }

        $counts = $game->launchLinks
            ->groupBy(fn (GameLaunchLink $link) => $link->status?->value ?? (string) $link->status)
            ->map->count();

        return [
            'ready' => (int) ($counts[GameLaunchStatus::Ready->value] ?? 0),
            'invalid' => (int) ($counts[GameLaunchStatus::Invalid->value] ?? 0),
            'draft' => (int) ($counts[GameLaunchStatus::Draft->value] ?? 0),
            'archived' => (int) ($counts[GameLaunchStatus::Archived->value] ?? 0),
            'total' => (int) $game->launchLinks->count(),
        ];
    }

    public function archiveLinks(Game $game): void
    {
        if (! $this->tableExists()) {
            return;
        }

        $game->loadMissing('launchLinks');

        $game->launchLinks->each(function (GameLaunchLink $link): void {
            $metadata = $link->metadata ?? [];
            $metadata['reason'] = 'game_unpublished';
            $metadata['message'] = 'Game hiện đang ở bản nháp nên liên kết này không còn sẵn sàng để phát hành.';

            $link->update([
                'status' => GameLaunchStatus::Archived->value,
                'last_verified_at' => null,
                'metadata' => $metadata,
            ]);
        });
    }

    protected function upsertWebPreviewLink(Game $game, GamePublicId $primaryPublicId, string $slug): GameLaunchLink
    {
        $miniAppPath = $this->miniAppPathFor($primaryPublicId, $slug);
        $runtimeBaseUrl = rtrim((string) config('game_launch.runtime_base_url', config('app.url')), '/');
        $runtimeUrl = $runtimeBaseUrl . $miniAppPath;

        return $game->launchLinks()->updateOrCreate(
            ['channel' => GameLaunchChannel::WebPreview->value],
            [
                'workspace_id' => $game->workspace_id,
                'entry_type' => GameLaunchEntryType::PublicId->value,
                'public_identifier' => $primaryPublicId->public_id,
                'miniapp_path' => $miniAppPath,
                'launch_url' => $runtimeUrl,
                'qr_payload' => $runtimeUrl,
                'status' => GameLaunchStatus::Ready->value,
                'metadata' => [
                    'slug' => $slug,
                    'channel_label' => 'Web Preview',
                ],
                'generated_at' => now(),
                'last_verified_at' => now(),
            ],
        );
    }

    protected function upsertZaloMiniAppLink(Game $game, GamePublicId $primaryPublicId, string $slug): GameLaunchLink
    {
        $miniAppPath = $this->miniAppPathFor($primaryPublicId, $slug);
        $template = config('game_launch.zalo_launch_url_template');
        $launchUrl = filled($template)
            ? $this->expandTemplate((string) $template, $primaryPublicId, $slug, $miniAppPath)
            : null;

        $status = filled($launchUrl) ? GameLaunchStatus::Ready : GameLaunchStatus::Invalid;
        $metadata = [
            'slug' => $slug,
            'channel_label' => 'Zalo Mini App',
        ];

        if (! filled($launchUrl)) {
            $metadata['reason'] = 'missing_zalo_launch_template';
            $metadata['message'] = 'Chưa cấu hình ZALO_MINI_APP_LAUNCH_URL_TEMPLATE trong môi trường hiện tại.';
        }

        return $game->launchLinks()->updateOrCreate(
            ['channel' => GameLaunchChannel::ZaloMiniApp->value],
            [
                'workspace_id' => $game->workspace_id,
                'entry_type' => GameLaunchEntryType::PublicId->value,
                'public_identifier' => $primaryPublicId->public_id,
                'miniapp_path' => $miniAppPath,
                'launch_url' => $launchUrl,
                'qr_payload' => $launchUrl ?: $this->buildFallbackPayload($primaryPublicId, $slug),
                'status' => $status->value,
                'metadata' => $metadata,
                'generated_at' => now(),
                'last_verified_at' => filled($launchUrl) ? now() : null,
            ],
        );
    }

    protected function resolvePrimaryPublicId(Game $game): GamePublicId
    {
        $primary = $game->publicIds->firstWhere('is_primary', true) ?? $game->publicIds->first();

        if ($primary) {
            return $primary;
        }

        return $game->publicIds()->create([
            'workspace_id' => $game->workspace_id,
            'public_id' => 'gm_' . Str::lower(Str::random(12)),
            'slug' => $game->slug,
            'is_primary' => true,
            'is_active' => true,
        ]);
    }

    protected function buildFallbackPayload(GamePublicId $primaryPublicId, string $slug): string
    {
        $miniAppPath = $this->miniAppPathFor($primaryPublicId, $slug);

        return rtrim((string) config('game_launch.runtime_base_url', config('app.url')), '/') . $miniAppPath;
    }

    protected function miniAppPathFor(GamePublicId $primaryPublicId, string $slug): string
    {
        return $this->expandTemplate(
            (string) config('game_launch.runtime_path_template', '/play/{public_id}'),
            $primaryPublicId,
            $slug,
        );
    }

    protected function expandTemplate(string $template, GamePublicId $primaryPublicId, string $slug, ?string $miniAppPath = null): string
    {
        $miniAppPath ??= '/play/' . $primaryPublicId->public_id;

        return strtr($template, [
            '{public_id}' => $primaryPublicId->public_id,
            '{slug}' => $slug,
            '{path}' => rawurlencode($miniAppPath),
            '{miniapp_path}' => $miniAppPath,
        ]);
    }
}
