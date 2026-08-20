<?php

namespace App\Console\Commands;

use App\Models\CampaignMaster;
use App\Services\FacebookLeadImporter;
use Illuminate\Console\Command;

class FacebookLeadIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:facebook-lead-integration {campaignId? : Campaign master ID to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Facebook leads for integrated campaigns';

    /**
     * Execute the console command.
     */
    public function handle(FacebookLeadImporter $importer): int
    {
        try {
            $campaignId = $this->argument('campaignId');

            if ($campaignId) {
                $campaign = CampaignMaster::findOrFail($campaignId);
                $summary = $importer->importCampaign($campaign);
            } else {
                $summary = $importer->importIntegratedCampaigns();
            }

            $this->info(sprintf(
                'Facebook lead import completed. Created: %d, Updated: %d, Skipped: %d, Failed: %d',
                $summary['created'] ?? 0,
                $summary['updated'] ?? 0,
                $summary['skipped'] ?? 0,
                $summary['failed'] ?? 0
            ));

            if (!empty($summary['failed']) && !empty($summary['errors'])) {
                $this->warn('Failed reason(s): ' . implode('; ', $summary['errors']));
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Facebook lead import failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
