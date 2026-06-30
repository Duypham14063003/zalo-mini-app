<?php

namespace App\Filament\Widgets;

use App\Models\Claim;
use App\Models\Player;
use App\Models\SpinAttempt;
use App\Models\SpinResult;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AdminOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'KPI chiến dịch';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        $workspaceIds = $user?->managedWorkspaceIds() ?? collect();

        $scopeToManagedWorkspaces = function (Builder $query) use ($user, $workspaceIds): Builder {
            return $query->when(
                $user && ! $user->isPlatformAdmin(),
                fn (Builder $workspaceQuery) => $workspaceQuery->whereIn('workspace_id', $workspaceIds),
            );
        };

        $participantsCount = $scopeToManagedWorkspaces(Player::query())->distinct('id')->count('id');
        $spinAttemptsCount = $scopeToManagedWorkspaces(SpinAttempt::query())->count();
        $winningResultsCount = $scopeToManagedWorkspaces(SpinResult::query())
            ->where('result_type', 'prize')
            ->count();
        $winningPlayersCount = $scopeToManagedWorkspaces(SpinResult::query())
            ->where('result_type', 'prize')
            ->distinct('player_id')
            ->count('player_id');
        $claimsCount = $scopeToManagedWorkspaces(Claim::query())->count();

        $winningRate = $spinAttemptsCount > 0
            ? round(($winningResultsCount / $spinAttemptsCount) * 100, 1)
            : null;
        $claimRate = $winningResultsCount > 0
            ? round(($claimsCount / $winningResultsCount) * 100, 1)
            : null;
        $campaignEffectiveness = $participantsCount > 0
            ? round(($claimsCount / $participantsCount) * 100, 1)
            : null;

        return [
            Stat::make('Tổng số người tham gia', number_format($participantsCount))
                ->description('Số người chơi duy nhất đã vào chiến dịch'),
            Stat::make('Tổng lượt quay', number_format($spinAttemptsCount))
                ->description('Tất cả lượt quay đã ghi nhận'),
            Stat::make('Tổng số người trúng', number_format($winningPlayersCount))
                ->description('Số người chơi duy nhất đã trúng thưởng'),
            Stat::make('Tỷ lệ trúng', $winningRate !== null ? "{$winningRate}%" : '0%')
                ->description('Số lượt trúng / tổng lượt quay'),
            Stat::make('Tỷ lệ đổi quà', $claimRate !== null ? "{$claimRate}%" : '0%')
                ->description('Số lượt nhận quà / số lượt trúng'),
            Stat::make('Tỷ lệ chia sẻ', 'Chưa bật tracking')
                ->description('Cần lưu event chia sẻ để tính chính xác'),
            Stat::make('Hiệu quả chiến dịch', $campaignEffectiveness !== null ? "{$campaignEffectiveness}%" : '0%')
                ->description('Số lượt nhận quà / tổng người tham gia'),
        ];
    }
}
