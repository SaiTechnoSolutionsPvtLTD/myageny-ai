<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class ExpireCompanies extends Command
{
    protected $signature = 'app:expire-companies';

    protected $description = 'Deactivate companies whose expiry date has passed and inactivate their users';

    public function handle(): int
    {
        $expiredCount = 0;
        $userCount = 0;

        Company::query()
            ->withCount('users')
            ->where('company_status', 'active')
            ->whereNotNull('expiry_date')
            ->get()
            ->each(function (Company $company) use (&$expiredCount, &$userCount) {
                if (! $company->isExpired()) {
                    return;
                }

                if ($company->syncExpiryState()) {
                    $expiredCount++;
                    $userCount += (int) $company->users_count;
                }
            });

        $this->info(sprintf(
            'Expired companies processed: %d, users inactivated: %d',
            $expiredCount,
            $userCount
        ));

        return self::SUCCESS;
    }
}
