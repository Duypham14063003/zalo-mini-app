<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Enums\GameLaunchChannel;
use App\Enums\GameLaunchStatus;
use App\Enums\GameStatus;
use App\Filament\Resources\GameResource;
use App\Services\GameBuilderService;
use App\Services\GameLaunchLinkService;
use DomainException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGame extends EditRecord
{
    protected static string $resource = GameResource::class;

    public function getTitle(): string
    {
        return 'Chỉnh sửa trò chơi';
    }

    public function getBreadcrumb(): string
    {
        return 'Chỉnh sửa';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $launchLinkService = app(GameLaunchLinkService::class);
        $builderConfig = app(GameBuilderService::class)->ensureConfig($record);
        $draft = $builderConfig->draft_config ?? app(GameBuilderService::class)->snapshotFromGame($record);
        if ($launchLinkService->tableExists()) {
            $record->loadMissing('launchLinks');
        }
        $launchData = $this->launchFormData($record);

        return [
            'name' => data_get($draft, 'general.name', $record->name),
            'public_slug' => data_get($draft, 'general.slug', $record->publicIds()->where('is_primary', true)->value('slug') ?: $record->slug),
            'status' => data_get($draft, 'general.status', $record->status?->value ?? 'active'),
            'game_description' => data_get($draft, 'general.description', $record->description),
            'requires_reward_code' => (bool) data_get($draft, 'rewards.requires_reward_code', false),
            'max_spins_per_player' => (int) data_get($draft, 'rewards.max_spins_per_player', 1),
            'prizes' => collect(data_get($draft, 'rewards.prizes', []))
                ->map(fn (array $prize) => [
                    'id' => $prize['id'] ?? null,
                    'code' => $prize['code'] ?? null,
                    'label' => $prize['label'] ?? '',
                    'description' => $prize['description'] ?? null,
                    'quota' => $prize['quota'] ?? null,
                    'weight' => $prize['weight'] ?? 0,
                    'is_active' => (bool) ($prize['is_active'] ?? false),
                ])
                ->all(),
            'primary_color' => data_get($draft, 'design.primary_color', '#f9c667'),
            'secondary_color' => data_get($draft, 'design.secondary_color', '#fff8e4'),
            'accent_color' => data_get($draft, 'design.accent_color', '#d79e2f'),
            'palette_preset' => data_get($draft, 'design.palette_preset', 'sunrise'),
            'border_preset' => data_get($draft, 'design.border_preset', 'classic-red'),
            'pointer_preset' => data_get($draft, 'design.pointer_preset', 'teardrop-gold'),
            'center_label' => data_get($draft, 'design.center_label', '19T'),
            'background_style' => data_get($draft, 'design.background_style', 'warm_gradient'),
            'preview_note' => data_get($draft, 'design.preview_note', ''),
            'title' => data_get($draft, 'presentation.title', ''),
            'subtitle' => data_get($draft, 'presentation.subtitle', ''),
            'presentation_description' => data_get($draft, 'presentation.description', ''),
            'spin_button' => data_get($draft, 'presentation.spin_button', 'Quay ngay'),
            'continue_button' => data_get($draft, 'presentation.continue_button', 'Tiep tuc'),
            'loading_message' => data_get($draft, 'presentation.loading_message', 'Dang tai...'),
            'redirect_action' => data_get($draft, 'presentation.redirect.action', 'open_oa'),
            'redirect_target_type' => data_get($draft, 'presentation.redirect.target_type'),
            'redirect_target_value' => data_get($draft, 'presentation.redirect.target_value'),
            'redirect_fallback_value' => data_get($draft, 'presentation.redirect.fallback_value'),
            'redirect_message_template' => data_get($draft, 'presentation.redirect.message_template'),
            'form_fields' => collect(data_get($draft, 'presentation.fields', []))
                ->map(fn (array $field) => [
                    'id' => $field['id'] ?? null,
                    'field_key' => $field['field_key'] ?? '',
                    'type' => $field['type'] ?? 'text',
                    'label' => $field['label'] ?? '',
                    'placeholder' => $field['placeholder'] ?? null,
                    'help_text' => $field['help_text'] ?? null,
                    'options_text' => collect($field['options'] ?? [])->implode("\n"),
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'is_active' => (bool) ($field['is_active'] ?? true),
                ])
                ->all(),
            ...$launchData,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $service = app(GameBuilderService::class);
        $builderConfig = $service->ensureConfig($record);

        $service->saveDraft($builderConfig, 'general', [
            'name' => $data['name'],
            'slug' => $data['public_slug'],
            'status' => $data['status'],
            'description' => $data['game_description'] ?? null,
        ]);

        $service->saveDraft($builderConfig->fresh(), 'rewards', [
            'requires_reward_code' => (bool) ($data['requires_reward_code'] ?? false),
            'max_spins_per_player' => (int) ($data['max_spins_per_player'] ?? 1),
            'prizes' => collect($data['prizes'] ?? [])
                ->map(fn (array $prize) => [
                    'id' => $prize['id'] ?? null,
                    'code' => $prize['code'] ?? null,
                    'label' => $prize['label'] ?? '',
                    'description' => $prize['description'] ?? null,
                    'quota' => $prize['quota'] !== '' ? $prize['quota'] : null,
                    'weight' => (int) ($prize['weight'] ?? 0),
                    'is_active' => (bool) ($prize['is_active'] ?? false),
                ])
                ->filter(fn (array $prize) => filled($prize['label']) || filled($prize['code']))
                ->values()
                ->all(),
        ]);

        $service->saveDraft($builderConfig->fresh(), 'design', [
            'primary_color' => $data['primary_color'],
            'secondary_color' => $data['secondary_color'],
            'accent_color' => $data['accent_color'],
            'palette_preset' => $data['palette_preset'],
            'border_preset' => $data['border_preset'],
            'pointer_preset' => $data['pointer_preset'],
            'center_label' => $data['center_label'],
            'background_style' => $data['background_style'],
            'preview_note' => $data['preview_note'] ?? null,
        ]);

        $service->saveDraft($builderConfig->fresh(), 'publish', [
            'title' => $data['title'],
            'subtitle' => $data['subtitle'],
            'description' => $data['presentation_description'] ?? null,
            'spin_button' => $data['spin_button'],
            'continue_button' => $data['continue_button'],
            'loading_message' => $data['loading_message'],
            'redirect' => [
                'action' => $data['redirect_action'],
                'target_type' => $data['redirect_target_type'] ?? null,
                'target_value' => $data['redirect_target_value'] ?? null,
                'fallback_value' => $data['redirect_fallback_value'] ?? null,
                'message_template' => $data['redirect_message_template'] ?? null,
            ],
            'fields' => collect($data['form_fields'] ?? [])
                ->map(function (array $field, int $index) {
                    $options = collect(explode("\n", (string) ($field['options_text'] ?? '')))
                        ->map(fn (string $option) => trim($option))
                        ->filter()
                        ->values()
                        ->all();

                    $fieldKey = trim((string) ($field['field_key'] ?? ''));
                    $label = trim((string) ($field['label'] ?? ''));

                    return [
                        'id' => $field['id'] ?? null,
                        'field_key' => $fieldKey !== '' ? str($fieldKey)->snake()->value() : str($label !== '' ? $label : 'custom_field_'.$index)->snake()->value(),
                        'type' => $field['type'] ?? 'text',
                        'label' => $label,
                        'placeholder' => $field['placeholder'] ?? null,
                        'help_text' => $field['help_text'] ?? null,
                        'is_required' => (bool) ($field['is_required'] ?? false),
                        'is_active' => (bool) ($field['is_active'] ?? true),
                        'options' => $options,
                    ];
                })
                ->filter(fn (array $field) => filled($field['field_key']) || filled($field['label']))
                ->values()
                ->all(),
        ]);

        $service->syncCurrentDraftToDatabase($record, $builderConfig->fresh());

        return $record->fresh([
            'builderConfig',
            'publicIds',
            'theme',
            'contentBlocks',
            'formFields',
            'rules',
            'redirects',
            'prizes',
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('Xuất bản')
                ->color('success')
                ->visible(fn (): bool => ! $this->isPublishedForUi($this->getRecord()))
                ->action(function (): void {
                    try {
                        $service = app(GameBuilderService::class);
                        $launchLinkService = app(GameLaunchLinkService::class);
                        $service->publish($this->getRecord(), $service->ensureConfig($this->getRecord()));
                        $relations = ['publicIds', 'builderConfig'];

                        if ($launchLinkService->tableExists()) {
                            $relations[] = 'launchLinks';
                        }

                        $this->record = $this->getRecord()->fresh($relations);
                        $this->fillForm();
                        $summary = $launchLinkService->summarizeStatuses($this->record);

                        Notification::make()
                            ->title('Trò chơi đã được xuất bản.')
                            ->body($summary['invalid'] > 0
                                ? 'Runtime đã xuất bản, nhưng vẫn còn kênh launch chưa sẵn sàng cho Zalo.'
                                : 'Runtime và liên kết launch đã sẵn sàng.')
                            ->success()
                            ->send();
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->title($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('unpublish')
                ->label('Hủy xuất bản')
                ->color('gray')
                ->visible(fn (): bool => $this->isPublishedForUi($this->getRecord()))
                ->action(function (): void {
                    $service = app(GameBuilderService::class);
                    $launchLinkService = app(GameLaunchLinkService::class);
                    $service->unpublish($this->getRecord(), $service->ensureConfig($this->getRecord()));
                    $launchLinkService->archiveLinks($this->getRecord()->fresh($launchLinkService->tableExists() ? ['launchLinks'] : []));
                    $relations = ['publicIds', 'builderConfig'];

                    if ($launchLinkService->tableExists()) {
                        $relations[] = 'launchLinks';
                    }

                    $this->record = $this->getRecord()->fresh($relations);
                    $this->fillForm();

                    Notification::make()
                        ->title('Trò chơi đã được chuyển về bản nháp.')
                        ->success()
                        ->send();
                }),
            Action::make('regenerateLaunchLinks')
                ->label('Tạo lại link')
                ->color('warning')
                ->visible(fn (): bool => $this->isPublishedForUi($this->getRecord()))
                ->action(function (): void {
                    $launchLinkService = app(GameLaunchLinkService::class);
                    $relations = ['publicIds'];

                    if ($launchLinkService->tableExists()) {
                        $relations[] = 'launchLinks';
                    }

                    $links = $launchLinkService->syncPublishedLinks($this->getRecord()->fresh($relations));

                    $relations[] = 'builderConfig';
                    $this->record = $this->getRecord()->fresh($relations);
                    $this->fillForm();

                    Notification::make()
                        ->title('Đã tạo lại liên kết triển khai.')
                        ->body("Đã cập nhật {$links->count()} kênh launch cho trò chơi này.")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function launchFormData(Model $record): array
    {
        $launchLinkService = app(GameLaunchLinkService::class);
        $relations = ['publicIds'];

        if ($launchLinkService->tableExists()) {
            $relations[] = 'launchLinks';
        }

        $record->loadMissing($relations);

        $primaryPublicId = $record->publicIds()->where('is_primary', true)->value('public_id')
            ?: $record->publicIds()->value('public_id')
            ?: 'Chưa có';

        $launchLinks = $launchLinkService->tableExists() ? $record->launchLinks : collect();
        $webPreview = $launchLinks->first(fn ($link) => $link->channel === GameLaunchChannel::WebPreview);
        $zaloMiniApp = $launchLinks->first(fn ($link) => $link->channel === GameLaunchChannel::ZaloMiniApp);
        $statusSummary = $launchLinkService->summarizeStatuses($record);
        $isPublished = $this->isPublishedForUi($record);

        $statusText = ! $launchLinkService->tableExists()
            ? 'Thiếu bảng game_launch_links'
            : (! $isPublished
            ? 'Game đang ở bản nháp'
            : ($statusSummary['total'] === 0
                ? 'Chưa tạo link'
                : "{$statusSummary['ready']} sẵn sàng / {$statusSummary['invalid']} chưa sẵn sàng"));

        $zaloMessage = ! $launchLinkService->tableExists()
            ? 'Database chưa có bảng game_launch_links. Hãy chạy php artisan migrate để kích hoạt tính năng link triển khai.'
            : (! $isPublished
            ? 'Game chưa được xuất bản công khai. Các link đã lưu chỉ mang tính tham chiếu và không nên chia sẻ cho người dùng.'
            : match ($zaloMiniApp?->status) {
            GameLaunchStatus::Ready => 'Kênh Zalo Mini App đã sẵn sàng để chia sẻ cho người dùng.',
            GameLaunchStatus::Invalid => $zaloMiniApp?->metadata['message'] ?? 'Kênh Zalo Mini App chưa sẵn sàng.',
            GameLaunchStatus::Archived => $zaloMiniApp?->metadata['message'] ?? 'Liên kết đã được lưu trữ.',
            default => 'Xuất bản game để tạo liên kết launch.',
        });

        return [
            'launch_public_identifier' => (string) $primaryPublicId,
            'launch_status_summary' => $statusText,
            'launch_runtime_url' => (string) ($webPreview?->launch_url ?? ''),
            'launch_miniapp_path' => (string) ($zaloMiniApp?->miniapp_path ?? $webPreview?->miniapp_path ?? ''),
            'launch_zalo_url' => (string) ($zaloMiniApp?->launch_url ?? ''),
            'launch_qr_payload' => (string) ($zaloMiniApp?->qr_payload ?? $webPreview?->qr_payload ?? ''),
            'launch_qr_preview_url' => $this->buildQrPreviewUrl((string) ($zaloMiniApp?->qr_payload ?? $webPreview?->qr_payload ?? '')),
            'launch_status_message' => $zaloMessage,
        ];
    }

    protected function buildQrPreviewUrl(string $payload): string
    {
        if ($payload === '') {
            return '';
        }

        return 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . rawurlencode($payload);
    }

    protected function isPublishedForUi(Model $record): bool
    {
        return (bool) $record->published_at
            && $record->builderConfig?->publication_status === 'published'
            && ($record->status?->value ?? $record->status) !== GameStatus::Draft->value;
    }
}
