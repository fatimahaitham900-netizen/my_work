<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\View\Composers\NavigationComposer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Gate::define('view-dashboard', static fn (User $user): bool => $user->is_active);
        Gate::define('manage-deviations', static fn (User $user): bool => $user->canManageDeviations());
        Gate::define('delete-deviations', static fn (User $user): bool => $user->canDeleteDeviations());
        Gate::define('view-audit-logs', static fn (User $user): bool => $user->canViewAuditLogs());

        View::composer('layouts.app', NavigationComposer::class);
    }
}
