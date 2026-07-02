<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Claim;
use App\Models\Game;
use App\Models\GameBuilderConfig;
use App\Models\GameContentBlock;
use App\Models\GameFormField;
use App\Models\GamePublicId;
use App\Models\GameRedirect;
use App\Models\GameRule;
use App\Models\GameTheme;
use App\Models\IntegrationConnection;
use App\Models\Prize;
use App\Models\RewardCode;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceThemeAsset;
use App\Observers\ConfigurationAuditObserver;
use App\Policies\GamePolicy;
use App\Policies\WorkspacePolicy;
use App\Policies\WorkspaceThemeAssetPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->isPlatformAdmin()) {
                return true;
            }

            return null;
        });

        Gate::policy(Workspace::class, WorkspacePolicy::class);
        Gate::policy(Game::class, GamePolicy::class);
        Gate::policy(WorkspaceThemeAsset::class, WorkspaceThemeAssetPolicy::class);

        foreach ([
            Account::class,
            Workspace::class,
            Game::class,
            GameBuilderConfig::class,
            GamePublicId::class,
            GameTheme::class,
            GameContentBlock::class,
            GameFormField::class,
            GameRule::class,
            GameRedirect::class,
            Prize::class,
            RewardCode::class,
            Claim::class,
            IntegrationConnection::class,
            WorkspaceThemeAsset::class,
        ] as $auditedModel) {
            $auditedModel::observe(ConfigurationAuditObserver::class);
        }
    }
}
