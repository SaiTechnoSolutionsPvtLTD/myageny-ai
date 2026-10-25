<?php

namespace Tests\Feature;

use App\Models\CampaignMaster;
use App\Models\User;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FacebookTokenUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([Authorize::class]);

        Schema::disableForeignKeyConstraints();

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('company_status')->default('active');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('user_status')->default('active');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('campaign_masters', function ($table) {
            $table->id();
            $table->string('camp_id')->nullable();
            $table->string('ad_id')->nullable();
            $table->string('campaign_name')->nullable();
            $table->text('access_token')->nullable();
            $table->integer('status')->default(1);
            $table->integer('is_integrated')->default(1);
            $table->string('new_fields')->default('0');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_accounts', function ($table) {
            $table->id();
            $table->string('act_id')->nullable();
            $table->string('act_name')->nullable();
            $table->text('token')->nullable();
        });
    }

    public function test_can_update_token_for_single_campaign(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);
        $this->actingAs($user);

        $campaign = CampaignMaster::create([
            'camp_id' => 'test_camp_123',
            'ad_id' => 'act_test_123',
            'campaign_name' => 'Test Campaign',
            'access_token' => 'old_token_12345678901234567890',
        ]);

        $newToken = 'EAAW4M0czKR4BStmBTUDIPdMiCmJTJ4C0JiuvCHWZAJazjs2Lejof0942QLlzgobhtVGZCZB9ZCRUDKdR3dRPFBw4NoibdjlLZC9NpMFHv0b8WLsZBBhI08GZBZCZA9ALySVCQ29zUUl9ZACR2w4ZA37trr5ZC70PYcssX2666Faa0j16DuiyqMIli85jIRhTpcCO';

        $response = $this->post(route('settings.facebook-integration.update-token'), [
            'target_type' => 'campaign',
            'campaign_id' => $campaign->id,
            'access_token' => $newToken,
        ]);

        $response->assertRedirect(route('settings.facebook-integration'));
        $this->assertEquals($newToken, $campaign->fresh()->access_token);
    }

    public function test_can_update_token_for_ad_account(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);
        $this->actingAs($user);

        DB::table('ad_accounts')->insert([
            'act_id' => 'act_999888',
            'act_name' => 'Test Account',
            'token' => 'old_ad_token_12345678901234567890',
        ]);

        $campaign = CampaignMaster::create([
            'camp_id' => 'test_camp_999',
            'ad_id' => 'act_999888',
            'campaign_name' => 'Account Campaign',
            'access_token' => 'old_ad_token_12345678901234567890',
        ]);

        $newToken = 'EAAW4M0czKR4BStmBTUDIPdMiCmJTJ4C0JiuvCHWZAJazjs2Lejof0942QLlzgobhtVGZCZB9ZCRUDKdR3dRPFBw4NoibdjlLZC9NpMFHv0b8WLsZBBhI08GZBZCZA9ALySVCQ29zUUl9ZACR2w4ZA37trr5ZC70PYcssX2666Faa0j16DuiyqMIli85jIRhTpcCO';

        $response = $this->post(route('settings.facebook-integration.update-token'), [
            'target_type' => 'ad_account',
            'ad_account_id' => 'act_999888',
            'access_token' => $newToken,
        ]);

        $response->assertRedirect(route('settings.facebook-integration'));
        $this->assertEquals($newToken, DB::table('ad_accounts')->where('act_id', 'act_999888')->value('token'));
        $this->assertEquals($newToken, $campaign->fresh()->access_token);
    }

    public function test_can_update_token_for_all_campaigns(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);
        $this->actingAs($user);

        DB::table('ad_accounts')->insert([
            'act_id' => 'act_all_1',
            'act_name' => 'Account All',
            'token' => 'old_token_12345678901234567890',
        ]);

        $campaign = CampaignMaster::create([
            'camp_id' => 'test_camp_all',
            'ad_id' => 'act_all_1',
            'campaign_name' => 'All Campaign',
            'access_token' => 'old_token_12345678901234567890',
        ]);

        $newToken = 'EAAW4M0czKR4BStmBTUDIPdMiCmJTJ4C0JiuvCHWZAJazjs2Lejof0942QLlzgobhtVGZCZB9ZCRUDKdR3dRPFBw4NoibdjlLZC9NpMFHv0b8WLsZBBhI08GZBZCZA9ALySVCQ29zUUl9ZACR2w4ZA37trr5ZC70PYcssX2666Faa0j16DuiyqMIli85jIRhTpcCO';

        $response = $this->post(route('settings.facebook-integration.update-token'), [
            'target_type' => 'all',
            'access_token' => $newToken,
        ]);

        $response->assertRedirect(route('settings.facebook-integration'));
        $this->assertEquals($newToken, DB::table('ad_accounts')->where('act_id', 'act_all_1')->value('token'));
        $this->assertEquals($newToken, $campaign->fresh()->access_token);
    }
}
