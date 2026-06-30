<?php

namespace App\Filament\Widgets;

use App\Models\PlayerSubmission;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RecentSubmissionsTable extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Lead gần đây';

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();
        $workspaceIds = $user?->managedWorkspaceIds() ?? collect();

        return $table
            ->query(
                PlayerSubmission::query()
                    ->with(['workspace', 'game', 'player'])
                    ->when(
                        $user && ! $user->isPlatformAdmin(),
                        fn (Builder $query) => $query->whereIn('game_id', function ($subQuery) use ($workspaceIds) {
                            $subQuery->select('id')
                                ->from('games')
                                ->whereIn('workspace_id', $workspaceIds);
                        })
                    )
                    ->latest('submitted_at')
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('workspace.name')
                    ->label('Workspace')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('player.full_name')
                    ->label('Người chơi')
                    ->default('Không xác định')
                    ->searchable(),
                TextColumn::make('player.phone')
                    ->label('Số điện thoại')
                    ->default('Chưa có')
                    ->searchable(),
                TextColumn::make('game.name')
                    ->label('Trò chơi')
                    ->searchable(),
                TextColumn::make('submitted_at')
                    ->label('Thời gian')
                    ->dateTime('d/m/Y H:i'),
            ])
            ->actions([
                Action::make('viewLead')
                    ->label('Xem chi tiết')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Đóng')
                    ->modalHeading(fn (PlayerSubmission $record): string => 'Chi tiết lead #' . $record->id)
                    ->modalContent(fn (PlayerSubmission $record) => view('filament.widgets.lead-detail', [
                        'submission' => $record,
                        'payloadRows' => $this->formatPayloadRows($record),
                    ])),
            ]);
    }

    /**
     * @return Collection<int, array{label: string, value: string}>
     */
    protected function formatPayloadRows(PlayerSubmission $submission): Collection
    {
        return collect($submission->payload ?? [])
            ->map(function (mixed $value, string|int $key): array {
                $normalizedValue = match (true) {
                    is_array($value) => implode(', ', array_map(
                        static fn (mixed $item): string => is_scalar($item)
                            ? (string) $item
                            : (json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''),
                        $value,
                    )),
                    is_bool($value) => $value ? 'Có' : 'Không',
                    $value === null || $value === '' => 'Chưa có dữ liệu',
                    default => (string) $value,
                };

                return [
                    'label' => $this->humanizePayloadKey((string) $key),
                    'value' => $normalizedValue,
                ];
            })
            ->values();
    }

    protected function humanizePayloadKey(string $key): string
    {
        $label = str($key)
            ->replace(['_', '-'], ' ')
            ->trim()
            ->ucsplit()
            ->flatten()
            ->join(' ');

        return $label !== '' ? $label : $key;
    }
}
