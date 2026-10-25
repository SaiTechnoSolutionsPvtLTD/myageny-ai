<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class QuotationCustomerResponseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

        Schema::create('leads', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('lead_date')->nullable();
            $table->string('lead_status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotations', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('quotation_no')->nullable();
            $table->date('quotation_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->decimal('tax', 5, 2)->default(0);
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->foreignId('lead_id')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->string('customer_response', 20)->default('pending');
            $table->timestamp('customer_responded_at')->nullable();
            $table->string('customer_signature')->nullable();
            $table->string('customer_location')->nullable();
            $table->string('customer_latitude', 30)->nullable();
            $table->string('customer_longitude', 30)->nullable();
            $table->string('customer_ip', 45)->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->text('bill_to_address')->nullable();
            $table->text('ship_to_address')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('customer_state')->nullable();
            $table->string('seller_state')->nullable();
            $table->string('tax_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotation_items', function ($table) {
            $table->id();
            $table->foreignId('quotation_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('item_name')->nullable();
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_email_template_contains_approve_and_reject_buttons(): void
    {
        $lead = Lead::create([
            'contact_name' => 'John Doe',
            'email'        => 'john@example.com',
        ]);

        $quotation = Quotation::create([
            'quotation_no'   => 'QT-2026-0001',
            'quotation_date' => now(),
            'valid_until'    => now()->addDays(7),
            'total_amount'   => 5000,
            'lead_id'        => $lead->id,
        ]);

        $agreeUrl = URL::signedRoute('quotations.customer-response', [
            'quotation' => $quotation->id,
            'response'  => Quotation::CUSTOMER_RESPONSE_AGREE,
        ]);
        $disagreeUrl = URL::signedRoute('quotations.customer-response', [
            'quotation' => $quotation->id,
            'response'  => Quotation::CUSTOMER_RESPONSE_DISAGREE,
        ]);

        $html = View::make('emails.quotation', [
            'quotation'   => $quotation,
            'lead'        => $lead,
            'agreeUrl'    => $agreeUrl,
            'disagreeUrl' => $disagreeUrl,
        ])->render();

        $this->assertStringContainsString('Approve', $html);
        $this->assertStringContainsString('Reject', $html);
        $this->assertStringContainsString($agreeUrl, $html);
        $this->assertStringContainsString($disagreeUrl, $html);
    }

    public function test_customer_response_get_renders_approval_page_with_signature_and_location_elements(): void
    {
        $lead = Lead::create([
            'contact_name' => 'Jane Smith',
            'email'        => 'jane@example.com',
        ]);

        $quotation = Quotation::create([
            'quotation_no'      => 'QT-2026-0002',
            'quotation_date'    => now(),
            'valid_until'       => now()->addDays(7),
            'total_amount'      => 12000,
            'lead_id'           => $lead->id,
            'is_approved'       => false,
            'customer_response' => 'pending',
        ]);

        $approveUrl = URL::signedRoute('quotations.customer-response', [
            'quotation' => $quotation->id,
            'response'  => Quotation::CUSTOMER_RESPONSE_AGREE,
        ]);

        $response = $this->get($approveUrl);

        $response->assertStatus(200);
        $response->assertSee('Approve Quotation');
        $response->assertSee('signaturePad');
        $response->assertSee('liveDateTime');
        $response->assertSee('locationStatus');
        $response->assertSee('12,000.00');
    }

    public function test_customer_can_submit_approval_with_signature_and_location(): void
    {
        $lead = Lead::create([
            'contact_name' => 'Jane Smith',
            'email'        => 'jane@example.com',
        ]);

        $quotation = Quotation::create([
            'quotation_no'      => 'QT-2026-0002',
            'quotation_date'    => now(),
            'valid_until'       => now()->addDays(7),
            'total_amount'      => 12000,
            'lead_id'           => $lead->id,
            'is_approved'       => false,
            'customer_response' => 'pending',
        ]);

        $submitUrl = URL::signedRoute('quotations.customer-response.submit', [
            'quotation' => $quotation->id,
            'response'  => Quotation::CUSTOMER_RESPONSE_AGREE,
        ]);

        $dummyBase64Signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->post($submitUrl, [
            'signature_data' => $dummyBase64Signature,
            'location'       => 'Coimbatore, Tamil Nadu, India',
            'latitude'       => '11.016844',
            'longitude'      => '76.955832',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Approved');

        $quotation->refresh();
        $this->assertTrue((bool) $quotation->is_approved);
        $this->assertNotNull($quotation->approved_at);
        $this->assertEquals(Quotation::CUSTOMER_RESPONSE_AGREE, $quotation->customer_response);
        $this->assertEquals('Approved', $quotation->customer_response_label);
        $this->assertNotNull($quotation->customer_responded_at);
        $this->assertNotNull($quotation->customer_signature);
        $this->assertStringContainsString('uploads/quotations/signatures/', $quotation->customer_signature);
        $this->assertEquals('Coimbatore, Tamil Nadu, India', $quotation->customer_location);
        $this->assertEquals('11.016844', $quotation->customer_latitude);
        $this->assertEquals('76.955832', $quotation->customer_longitude);

        // Clean up created signature file
        if ($quotation->customer_signature && file_exists(public_path($quotation->customer_signature))) {
            @unlink(public_path($quotation->customer_signature));
        }
    }

    public function test_customer_can_reject_quotation(): void
    {
        $lead = Lead::create([
            'contact_name' => 'Bob Builder',
            'email'        => 'bob@example.com',
        ]);

        $quotation = Quotation::create([
            'quotation_no'      => 'QT-2026-0003',
            'quotation_date'    => now(),
            'valid_until'       => now()->addDays(7),
            'total_amount'      => 8500,
            'lead_id'           => $lead->id,
            'is_approved'       => false,
            'customer_response' => 'pending',
        ]);

        $rejectUrl = URL::signedRoute('quotations.customer-response', [
            'quotation' => $quotation->id,
            'response'  => Quotation::CUSTOMER_RESPONSE_DISAGREE,
        ]);

        $response = $this->get($rejectUrl);
        $response->assertStatus(200);
        $response->assertSee('Reject Quotation?');

        $submitRejectUrl = URL::signedRoute('quotations.customer-response.submit', [
            'quotation' => $quotation->id,
            'response'  => Quotation::CUSTOMER_RESPONSE_DISAGREE,
        ]);

        $submitResponse = $this->post($submitRejectUrl);
        $submitResponse->assertStatus(200);
        $submitResponse->assertSee('Rejected');

        $quotation->refresh();
        $this->assertFalse((bool) $quotation->is_approved);
        $this->assertEquals(Quotation::CUSTOMER_RESPONSE_DISAGREE, $quotation->customer_response);
        $this->assertEquals('Rejected', $quotation->customer_response_label);
        $this->assertNotNull($quotation->customer_responded_at);
    }
}
