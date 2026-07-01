<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\PlayerSubmission;
use App\Models\SpinResult;
use App\Models\Workspace;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $workspaceCount = $this->workspaceQuery($request)->count();
        $games = $this->gameQuery($request)
            ->with(['workspace', 'theme', 'rules', 'publicIds'])
            ->withCount([
                'players',
                'prizes',
                'spinResults as winning_results_count' => fn (Builder $query) => $query->where('result_type', 'prize'),
                'spinResults as winning_players_count' => fn (Builder $query) => $query
                    ->select(DB::raw('count(distinct player_id)'))
                    ->where('result_type', 'prize'),
            ])
            ->latest('updated_at')
            ->get();

        $recentSubmissions = PlayerSubmission::query()
            ->with(['game', 'player'])
            ->whereIn('game_id', $games->pluck('id'))
            ->latest('submitted_at')
            ->limit(8)
            ->get();

        $stats = [
            'workspace_count' => $workspaceCount,
            'game_count' => $games->count(),
            'submission_count' => PlayerSubmission::query()
                ->whereIn('game_id', $games->pluck('id'))
                ->count(),
            'winning_player_count' => SpinResult::query()
                ->whereIn('game_id', $games->pluck('id'))
                ->where('result_type', 'prize')
                ->distinct('player_id')
                ->count('player_id'),
            'role_label' => str($user?->platform_role?->value ?? 'workspace_owner')
                ->replace('_', ' ')
                ->title()
                ->value(),
        ];

        return view('dashboard', [
            'stats' => $stats,
            'games' => $games,
            'recentSubmissions' => $recentSubmissions,
        ]);
    }

    /**
     * @return Builder<Workspace>
     */
    protected function workspaceQuery(Request $request): Builder
    {
        $user = $request->user();

        return Workspace::query()
            ->when(
                ! $user->isPlatformAdmin(),
                fn (Builder $query) => $query->whereIn('id', $user->managedWorkspaceIds())
            );
    }

    /**
     * @return Builder<Game>
     */
    protected function gameQuery(Request $request): Builder
    {
        $user = $request->user();

        return Game::query()
            ->when(
                ! $user->isPlatformAdmin(),
                fn (Builder $query) => $query->whereIn('workspace_id', $user->managedWorkspaceIds())
            );
    }
}
