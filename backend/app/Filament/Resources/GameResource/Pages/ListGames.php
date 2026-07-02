<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Enums\GameStatus;
use App\Enums\GameTemplateType;
use App\Enums\WorkspaceMembershipRole;
use App\Filament\Resources\GameResource;
use App\Models\Account;
use App\Models\Game;
use App\Models\GamePublicId;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Services\GameBuilderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\WorkspaceThemeAssetService;

class ListGames extends ListRecords
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createGame')
                ->label('Tạo trò chơi')
                ->icon('heroicon-o-plus')
                ->modalHeading('Tạo trò chơi mới')
                ->modalSubmitActionLabel('Tạo và mở builder')
                ->form([
                    Select::make('workspace_id')
                        ->label('Workspace')
                        ->options(fn (): array => $this->getWorkspaceOptions())
                        ->required()
                        ->searchable()
                        ->default(fn (): ?int => array_key_first($this->getWorkspaceOptions()))
                        ->createOptionForm([
                            TextInput::make('workspace_name')
                                ->label('Tên workspace')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, callable $set): void {
                                    if (blank($state)) {
                                        return;
                                    }

                                    $set('workspace_slug', Str::slug($state));
                                }),
                            TextInput::make('workspace_slug')
                                ->label('Slug workspace')
                                ->required()
                                ->maxLength(255)
                                ->rule('alpha_dash:ascii')
                                ->helperText('Ví dụ: ohar-workspace'),
                        ])
                        ->createOptionUsing(fn (array $data): int => $this->createWorkspaceOption($data)),
                    TextInput::make('name')
                        ->label('Tên trò chơi')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('Slug công khai')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Ví dụ: vong-quay-mua-he')
                        ->rule('alpha_dash:ascii')
                        ->unique(GamePublicId::class, 'slug'),
                    Select::make('status')
                        ->label('Trạng thái')
                        ->options([
                            GameStatus::Draft->value => 'Bản nháp',
                            GameStatus::Active->value => 'Đang hoạt động',
                            GameStatus::Inactive->value => 'Tạm dừng',
                        ])
                        ->default(GameStatus::Draft->value)
                        ->required(),
                    Textarea::make('description')
                        ->label('Mô tả')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $game = DB::transaction(function () use ($data) {
                        $workspace = Workspace::query()->findOrFail($data['workspace_id']);

                        $game = Game::query()->create([
                            'workspace_id' => $workspace->id,
                            'name' => $data['name'],
                            'slug' => $data['slug'],
                            'template_type' => GameTemplateType::LuckyWheel->value,
                            'status' => $data['status'],
                            'description' => $data['description'] ?? null,
                        ]);

                        GamePublicId::query()->create([
                            'workspace_id' => $workspace->id,
                            'game_id' => $game->id,
                            'public_id' => 'gm_' . Str::lower(Str::random(12)),
                            'slug' => $data['slug'],
                            'is_primary' => true,
                            'is_active' => true,
                        ]);

                        app(GameBuilderService::class)->ensureConfig($game);

                        return $game;
                    });

                    Notification::make()
                        ->title('Đã tạo trò chơi mới.')
                        ->success()
                        ->send();

                    $this->redirect(GameResource::getUrl('edit', ['record' => $game]));
                }),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getWorkspaceOptions(): array
    {
        $user = Filament::auth()->user();

        return Workspace::query()
            ->when(
                $user && ! $user->isPlatformAdmin(),
                fn ($query) => $query->whereIn('id', $user->managedWorkspaceIds()),
            )
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    protected function createWorkspaceOption(array $data): int
    {
        $user = Filament::auth()->user();
        $workspaceName = trim((string) ($data['workspace_name'] ?? ''));
        $workspaceSlug = $this->makeUniqueSlug(
            $data['workspace_slug'] ?? $workspaceName,
            Workspace::class,
        );
        $accountSlug = $this->makeUniqueSlug($workspaceSlug, Account::class);

        return DB::transaction(function () use ($user, $workspaceName, $workspaceSlug, $accountSlug): int {
            $account = Account::query()->create([
                'owner_user_id' => $user?->id,
                'name' => $workspaceName,
                'slug' => $accountSlug,
                'status' => 'active',
            ]);

            $workspace = Workspace::query()->create([
                'account_id' => $account->id,
                'name' => $workspaceName,
                'slug' => $workspaceSlug,
                'status' => 'active',
                'timezone' => 'Asia/Ho_Chi_Minh',
            ]);

            if ($user) {
                WorkspaceMembership::query()->firstOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'role' => WorkspaceMembershipRole::WorkspaceOwner,
                        'is_primary' => false,
                    ],
                );
            }

            app(WorkspaceThemeAssetService::class)->ensureStarterAssets();

            return $workspace->id;
        });
    }

    protected function makeUniqueSlug(string $value, string $modelClass): string
    {
        $baseSlug = Str::slug($value);
        $baseSlug = filled($baseSlug) ? $baseSlug : 'workspace';
        $slug = $baseSlug;
        $suffix = 2;

        while ($modelClass::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
