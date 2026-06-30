<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogWriter
{
    public function logModelChange(
        string $action,
        Model $model,
        ?array $beforeState = null,
        ?array $afterState = null,
    ): void {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        AuditLog::create([
            'workspace_id' => $this->resolveWorkspaceId($model),
            'game_id' => $this->resolveGameId($model),
            'actor_user_id' => $actor->id,
            'actor_type' => 'user',
            'action' => $action,
            'target_type' => $model->getMorphClass(),
            'target_id' => (string) $model->getKey(),
            'before_state' => $beforeState,
            'after_state' => $afterState,
        ]);
    }

    protected function resolveWorkspaceId(Model $model): ?int
    {
        if (isset($model->workspace_id)) {
            return (int) $model->workspace_id;
        }

        if ($model instanceof Game) {
            return $model->workspace_id;
        }

        if (isset($model->game_id) && method_exists($model, 'game')) {
            /** @var Game|null $game */
            $game = $model->relationLoaded('game') ? $model->getRelation('game') : $model->game()->first();

            return $game?->workspace_id;
        }

        return null;
    }

    protected function resolveGameId(Model $model): ?int
    {
        if ($model instanceof Game) {
            return $model->getKey();
        }

        if (isset($model->game_id)) {
            return (int) $model->game_id;
        }

        return null;
    }
}
