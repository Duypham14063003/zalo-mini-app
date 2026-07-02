<?php

namespace App\Services;

use App\Models\Game;
use App\Models\WorkspaceThemeAsset;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkspaceThemeAssetService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function slotDefinitions(): array
    {
        return [
            'background' => [
                'label' => 'Hình nền game',
                'directory' => 'workspace-theme-assets/backgrounds',
                'accept' => ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'],
            ],
            'banner' => [
                'label' => 'Banner trên',
                'directory' => 'workspace-theme-assets/banners',
                'accept' => ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'],
            ],
            'spin_button' => [
                'label' => 'Nút quay thưởng',
                'directory' => 'workspace-theme-assets/spin-buttons',
                'accept' => ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'],
            ],
            'extra_spin_button' => [
                'label' => 'Nút thêm lượt',
                'directory' => 'workspace-theme-assets/extra-spin-buttons',
                'accept' => ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'],
            ],
            'wheel_border' => [
                'label' => 'Viền vòng quay',
                'directory' => 'workspace-theme-assets/wheel-borders',
                'accept' => ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'],
            ],
            'wheel_pointer' => [
                'label' => 'Mũi tên vòng quay',
                'directory' => 'workspace-theme-assets/wheel-pointers',
                'accept' => ['image/png'],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public function starterAssets(): array
    {
        return [
            'background' => [
                ['slug' => 'bg-sunburst', 'name' => 'Sunburst đỏ cam', 'source' => 'bg/bg1.png', 'mime' => 'image/png'],
            ],
            'wheel_border' => [
                ['slug' => 'wheel-pink-star', 'name' => 'Hồng ánh sao', 'source' => 'bg-vongquay/v4.png', 'mime' => 'image/png'],
                ['slug' => 'wheel-classic-red', 'name' => 'Đỏ cổ điển', 'source' => 'bg-vongquay/v2.png', 'mime' => 'image/png'],
                ['slug' => 'wheel-gold-ring', 'name' => 'Vòng vàng', 'source' => 'bg-vongquay/v3.png', 'mime' => 'image/png'],
                ['slug' => 'wheel-violet-glow', 'name' => 'Tím phát sáng', 'source' => 'bg-vongquay/v1.png', 'mime' => 'image/png'],
            ],
            'banner' => [
                ['slug' => 'banner-default', 'name' => 'Banner LanEm', 'source' => 'resources/theme-presets/banner-lanem.svg', 'mime' => 'image/svg+xml'],
            ],
            'spin_button' => [
                ['slug' => 'spin-button-default', 'name' => 'Nút quay mặc định', 'source' => 'resources/theme-presets/spin-button.svg', 'mime' => 'image/svg+xml'],
            ],
            'extra_spin_button' => [
                ['slug' => 'extra-spin-button-default', 'name' => 'Nút thêm lượt mặc định', 'source' => 'resources/theme-presets/extra-spin-button.svg', 'mime' => 'image/svg+xml'],
            ],
            'wheel_pointer' => [
                ['slug' => 'pointer-default', 'name' => 'Mũi tên mặc định', 'source' => 'resources/theme-presets/wheel-pointer.svg', 'mime' => 'image/svg+xml'],
            ],
        ];
    }

    public function ensureStarterAssets(): void
    {
        foreach ($this->starterAssets() as $slotType => $assets) {
            foreach ($assets as $index => $asset) {
                $storedPath = $this->copyStarterAsset(
                    $slotType,
                    $asset['slug'],
                    $asset['source'],
                );

                WorkspaceThemeAsset::query()->updateOrCreate(
                    [
                        'workspace_id' => null,
                        'slot_type' => $slotType,
                        'source_kind' => 'builtin',
                        'asset_path' => $storedPath,
                    ],
                    [
                        'display_name' => $asset['name'],
                        'mime_type' => $asset['mime'],
                        'is_active' => true,
                        'sort_order' => $index + 1,
                        'metadata' => [
                            'seed_slug' => $asset['slug'],
                            'source' => $asset['source'],
                        ],
                    ],
                );
            }
        }
    }

    public function assetUrl(?string $assetPath): ?string
    {
        if (! filled($assetPath)) {
            return null;
        }

        return Storage::disk('public')->url((string) $assetPath);
    }

    public function storageDirectory(int|string|null $workspaceId, string $slotType): string
    {
        $definition = $this->slotDefinitions()[$slotType] ?? null;
        $baseDirectory = $definition['directory'] ?? 'workspace-theme-assets/misc';

        return $baseDirectory.'/shared';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function builderAssetSelections(?Game $game, array $design): array
    {
        $assets = $this->activeGlobalAssets();
        $resolved = [];

        foreach (array_keys($this->slotDefinitions()) as $slotType) {
            $presetIdKey = $this->presetIdKey($slotType);
            $overridePathKey = $this->overridePathKey($slotType);
            $presetId = Arr::get($design, $presetIdKey);
            $overridePath = Arr::get($design, $overridePathKey);
            $slotAssets = $assets->get($slotType, collect());
            $preset = $presetId ? $slotAssets->firstWhere('id', (int) $presetId) : null;

            $resolved[$slotType] = [
                'preset_id' => $presetId ? (int) $presetId : null,
                'override_path' => $overridePath,
                'preview_url' => $this->assetUrl($overridePath)
                    ?? ($preset ? $this->assetUrl($preset->asset_path) : null),
                'preset_label' => $preset?->display_name,
            ];
        }

        return $resolved;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveForGame(Game $game, array $design): array
    {
        $assets = $this->activeGlobalAssets();
        $resolved = [];

        foreach (array_keys($this->slotDefinitions()) as $slotType) {
            $presetIdKey = $this->presetIdKey($slotType);
            $overridePathKey = $this->overridePathKey($slotType);
            $overridePath = Arr::get($design, $overridePathKey);
            $presetId = Arr::get($design, $presetIdKey);
            $preset = $presetId ? $assets->get($slotType, collect())->firstWhere('id', (int) $presetId) : null;
            $fallback = $this->legacyFallback($slotType, $design);

            $source = 'none';
            $assetPath = null;
            $assetUrl = null;
            $label = null;

            if (filled($overridePath)) {
                $source = 'override';
                $assetPath = (string) $overridePath;
                $assetUrl = $this->assetUrl($assetPath);
            } elseif ($preset) {
                $source = 'shared_preset';
                $assetPath = $preset->asset_path;
                $assetUrl = $this->assetUrl($preset->asset_path);
                $label = $preset->display_name;
            } elseif ($fallback) {
                $source = 'builtin_default';
                $assetPath = $fallback['asset_path'] ?? null;
                $assetUrl = $fallback['asset_url'] ?? null;
                $label = $fallback['label'] ?? null;
            }

            $resolved[$slotType] = [
                'slotType' => $slotType,
                'presetId' => $presetId ? (int) $presetId : null,
                'overridePath' => $overridePath,
                'assetPath' => $assetPath,
                'assetUrl' => $assetUrl,
                'label' => $label,
                'source' => $source,
            ];
        }

        return $resolved;
    }

    /**
     * @return array<int, string>
     */
    public function acceptedMimeTypes(string $slotType): array
    {
        return $this->slotDefinitions()[$slotType]['accept'] ?? ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];
    }

    /**
     * @return array<int, string>
     */
    public function selectOptionsForWorkspace(?int $workspaceId, string $slotType): array
    {
        return WorkspaceThemeAsset::query()
            ->where('slot_type', $slotType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->pluck('display_name', 'id')
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pickerOptionsForWorkspace(?int $workspaceId, string $slotType): array
    {
        return WorkspaceThemeAsset::query()
            ->where('slot_type', $slotType)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->get()
            ->map(fn (WorkspaceThemeAsset $asset) => [
                'id' => $asset->id,
                'label' => $asset->display_name,
                'preview_url' => $this->assetUrl($asset->asset_path),
                'asset_path' => $asset->asset_path,
            ])
            ->all();
    }

    public function presetIdKey(string $slotType): string
    {
        return $slotType.'_preset_id';
    }

    public function overridePathKey(string $slotType): string
    {
        return match ($slotType) {
            'wheel_border' => 'border_asset_path',
            'background' => 'background_asset_path',
            default => $slotType.'_asset_path',
        };
    }

    /**
     * @return Collection<string, Collection<int, WorkspaceThemeAsset>>
     */
    protected function activeGlobalAssets(): Collection
    {
        return WorkspaceThemeAsset::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('display_name')
            ->get()
            ->groupBy('slot_type');
    }

    /**
     * @return array<string, string>|null
     */
    protected function legacyFallback(string $slotType, array $design): ?array
    {
        return match ($slotType) {
            'background' => $this->fallbackBackground($design),
            'wheel_border' => $this->fallbackBorder($design),
            default => null,
        };
    }

    /**
     * @return array<string, string>|null
     */
    protected function fallbackBackground(array $design): ?array
    {
        $style = (string) Arr::get($design, 'background_style', 'warm_gradient');

        if ($style !== 'bg_showcase') {
            return null;
        }

        $starter = collect($this->starterAssets()['background'] ?? [])->first();

        if (! $starter) {
            return null;
        }

        $storedPath = $this->starterStoredPath('background', $starter['slug'], $starter['source']);

        return [
            'label' => $starter['name'],
            'asset_path' => $storedPath,
            'asset_url' => $this->assetUrl($storedPath),
        ];
    }

    /**
     * @return array<string, string>|null
     */
    protected function fallbackBorder(array $design): ?array
    {
        $preset = (string) Arr::get($design, 'border_preset', 'pink-star');
        $mapping = [
            'pink-star' => 'wheel-pink-star',
            'classic-red' => 'wheel-classic-red',
            'gold-ring' => 'wheel-gold-ring',
            'violet-glow' => 'wheel-violet-glow',
        ];

        $slug = $mapping[$preset] ?? null;

        if (! $slug) {
            return null;
        }

        $starter = collect($this->starterAssets()['wheel_border'] ?? [])
            ->firstWhere('slug', $slug);

        if (! $starter) {
            return null;
        }

        $storedPath = $this->starterStoredPath('wheel_border', $starter['slug'], $starter['source']);

        return [
            'label' => $starter['name'],
            'asset_path' => $storedPath,
            'asset_url' => $this->assetUrl($storedPath),
        ];
    }

    protected function builtinPreviewUrl(string $relativeSourcePath): ?string
    {
        foreach ($this->starterAssets() as $slotType => $assets) {
            $starter = collect($assets)->firstWhere('source', $relativeSourcePath);

            if (! $starter) {
                continue;
            }

            $storedPath = $this->starterStoredPath($slotType, $starter['slug'], $starter['source']);

            return $this->assetUrl($storedPath);
        }

        return null;
    }

    protected function copyStarterAsset(string $slotType, string $slug, string $sourcePath): string
    {
        $storedPath = $this->starterStoredPath($slotType, $slug, $sourcePath);

        if (! Storage::disk('public')->exists($storedPath)) {
            Storage::disk('public')->put($storedPath, (string) file_get_contents(base_path($sourcePath)));
        }

        return $storedPath;
    }

    protected function starterStoredPath(string $slotType, string $slug, string $sourcePath): string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'png';

        return $this->storageDirectory(null, $slotType).'/starter-'.$slug.'.'.$extension;
    }

    protected function guessMimeType(string $path): ?string
    {
        return match (Str::lower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => null,
        };
    }
}
