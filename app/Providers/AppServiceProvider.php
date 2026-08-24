<?php

namespace App\Providers;

use App\Models\Lead;
use App\Models\User;
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
            if ($user->isSystemAdmin()) {
                return true;
            }

            return $user->hasCrmPermission($ability) ? true : null;
        });

        // Mobile CST Updates route guard — mirrors the inline
        // abort_unless($user->isCustomerSuccessUser()) check web's
        // LeadController::storeCstUpdate() uses, but wired as a proper
        // route-level Gate for the mobile API (routes/api.php:
        // ->middleware('can:add-cst-update,lead')).
        Gate::define('add-cst-update', function (User $user, Lead $lead) {
            return $user->allowsCstUpdates() && $user->isCustomerSuccessUser();
        });
    }
}
