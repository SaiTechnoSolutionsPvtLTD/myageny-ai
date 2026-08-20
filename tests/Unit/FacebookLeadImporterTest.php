<?php

namespace Tests\Unit;

use App\Models\CampaignFieldMigration;
use App\Models\LeadFormField;
use App\Services\FacebookLeadImporter;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FacebookLeadImporterTest extends TestCase
{
    public function test_custom_mapping_uses_lead_field_id_when_field_name_is_blank(): void
    {
        $leadField = new LeadFormField([
            'label' => 'Budget',
            'field_name' => null,
        ]);
        $leadField->id = 7;

        $result = $this->mapSubmissionValues(
            [
                'field_data' => [
                    ['name' => 'budget', 'values' => ['5000']],
                ],
            ],
            new Collection([
                new CampaignFieldMigration([
                    'campaign_field_name' => 'budget',
                    'lead_field_id' => 7,
                    'crm_field_name' => 'custom:7',
                ]),
            ]),
            (new Collection([$leadField]))->keyBy('id')
        );

        $this->assertSame([], $result['core']);
        $this->assertSame([7 => '5000'], $result['custom']);
    }

    public function test_core_mapping_still_maps_to_lead_columns(): void
    {
        $result = $this->mapSubmissionValues(
            [
                'field_data' => [
                    ['name' => 'full_name', 'values' => ['Ravi Kumar']],
                ],
            ],
            new Collection([
                new CampaignFieldMigration([
                    'campaign_field_name' => 'full_name',
                    'lead_field_id' => null,
                    'crm_field_name' => 'contact_name',
                ]),
            ]),
            new Collection()
        );

        $this->assertSame(['contact_name' => 'Ravi Kumar'], $result['core']);
        $this->assertSame([], $result['custom']);
    }

    public function test_sanitize_error_message_hides_access_tokens(): void
    {
        $importer = new FacebookLeadImporter();
        $secretMessage = 'Graph API failure access_token=EAAX123456789012345678901234567890 details';
        $sanitized = $importer->sanitizeErrorMessage($secretMessage);

        $this->assertStringNotContainsString('EAAX123456789012345678901234567890', $sanitized);
        $this->assertStringContainsString('access_token=[hidden]', $sanitized);
    }

    private function mapSubmissionValues(array $submission, Collection $fieldMappings, Collection $leadFields): array
    {
        $method = new ReflectionMethod(FacebookLeadImporter::class, 'mapSubmissionValues');
        $method->setAccessible(true);

        return $method->invoke(new FacebookLeadImporter(), $submission, $fieldMappings, $leadFields);
    }
}
