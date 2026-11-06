<?php

namespace App\Providers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Pagination\Paginator;
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
        Paginator::defaultView('partials.table-pagination');

        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            // Quotation approval is strictly prohibited for branch admin, branch manager, and hr admin
            if ($ability === 'quotations.approve') {
                if ($user->isBranchAdmin() || $user->isBranchManager() || $user->isHrAdmin() || collect($user->roleKeys()->all())->intersect(['branch_admin', 'branch_manager', 'bm', 'hr_admin'])->isNotEmpty()) {
                    return false;
                }
            }

            return $user->hasCrmPermission($ability) ? true : null;
        });

        // Quotations approval & access gates: Admin and HR can approve and view
        Gate::define('quotations.approve', function (User $user) {
            return $user->canApproveQuotation();
        });

        Gate::define('quotations.view', function (User $user) {
            return $user->canApproveQuotation() || $user->hasCrmPermission('quotations.view');
        });

        Gate::define('quotations.menuview', function (User $user) {
            return $user->canApproveQuotation() || $user->hasCrmPermission('quotations.menuview');
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
