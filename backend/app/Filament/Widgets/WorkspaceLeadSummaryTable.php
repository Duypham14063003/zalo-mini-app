<?php

namespace App\Filament\Widgets;

use App\Models\Workspace;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class WorkspaceLeadSummaryTable extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Hiệu quả theo workspace';

    public function table(Table $table): Table
    {
        $user = Filament::auth()->user();
        $workspaceIds = $user?->managedWorkspaceIds() ?? collect();

        return $table
            ->query(
                Workspace::query()
                    ->select('workspaces.*')
                    ->selectSub(
                        DB::table('players')
                            ->selectRaw('count(*)')
                            ->whereColumn('players.workspace_id', 'workspaces.id'),
                        'players_count',
                    )
                    ->selectSub(
                        DB::table('spin_attempts')
                            ->selectRaw('count(*)')
                            ->whereColumn('spin_attempts.workspace_id', 'workspaces.id'),
                        'spin_attempts_count',
                    )
                    ->selectSub(
                        DB::table('spin_results')
                            ->selectRaw('count(*)')
                            ->whereColumn('spin_results.workspace_id', 'workspaces.id')
                            ->where('result_type', 'prize'),
                        'winning_results_count',
                    )
                    ->selectSub(
                        DB::table('spin_results')
                            ->selectRaw('count(distinct player_id)')
                            ->whereColumn('spin_results.workspace_id', 'workspaces.id')
                            ->where('result_type', 'prize'),
                        'winning_players_count',
                    )
                    ->selectSub(
                        DB::table('claims')
                            ->selectRaw('count(*)')
                            ->whereColumn('claims.workspace_id', 'workspaces.id'),
                        'claims_count',
                    )
                    ->selectSub(
                        DB::table('claims')
                            ->selectRaw('max(claimed_at)')
                            ->whereColumn('claims.workspace_id', 'workspaces.id'),
                        'claims_max_claimed_at',
                    )
                    ->when(
                        $user && ! $user->isPlatformAdmin(),
                        fn (Builder $query) => $query->whereIn('id', $workspaceIds),
                    )
                    ->orderByDesc('claims_count')
                    ->orderBy('name')
            )
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('name')
                    ->label('Workspace')
                    ->searchable()
                    ->weight('600')
                    ->description(fn (Workspace $record): string => $record->slug),
                TextColumn::make('players_count')
                    ->label('Người tham gia')
                    ->alignCenter()
                    ->numeric(),
                TextColumn::make('spin_attempts_count')
                    ->label('Lượt quay')
                    ->alignCenter()
                    ->numeric()
                    ->badge()
                    ->color('warning'),
                TextColumn::make('winning_players_count')
                    ->label('Người trúng')
                    ->alignCenter()
                    ->numeric()
                    ->badge()
                    ->color('success'),
                TextColumn::make('winning_rate')
                    ->label('Tỷ lệ trúng')
                    ->state(function (Workspace $record): string {
                        if ((int) $record->spin_attempts_count === 0) {
                            return '0%';
                        }

                        return number_format(((int) $record->winning_results_count / (int) $record->spin_attempts_count) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color('info'),
                TextColumn::make('claim_rate')
                    ->label('Tỷ lệ đổi quà')
                    ->state(function (Workspace $record): string {
                        if ((int) $record->winning_results_count === 0) {
                            return '0%';
                        }

                        return number_format(((int) $record->claims_count / (int) $record->winning_results_count) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color('primary'),
                TextColumn::make('campaign_effectiveness')
                    ->label('Hiệu quả chiến dịch')
                    ->state(function (Workspace $record): string {
                        if ((int) $record->players_count === 0) {
                            return '0%';
                        }

                        return number_format(((int) $record->claims_count / (int) $record->players_count) * 100, 1) . '%';
                    })
                    ->badge()
                    ->color('gray'),
                TextColumn::make('claims_max_claimed_at')
                    ->label('Đổi quà gần nhất')
                    ->placeholder('Chưa có đổi quà')
                    ->since(),
            ]);
    }
}
