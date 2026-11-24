<?php

namespace Tests\Feature;

use App\Models\Courier;
use App\Models\CourierForm;
use App\Models\CourierFormField;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CourierModuleTest extends TestCase
{
    private User $adminUser;
    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Schema::disableForeignKeyConstraints();

        foreach ([
            'courier_values',
            'couriers',
            'courier_form_fields',
            'courier_forms',
            'outside_office_attendance_requests',
            'production_initiations',
            'activity_logs',
            'notifications',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
            'users',
            'companies',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('company_status')->default('active');
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->string('display_name')->nullable();
            $table->string('module')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('user_status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notifications', function ($table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('action')->nullable();
            $table->string('module')->nullable();
            $table->string('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('production_initiations', function ($table) {
            $table->id();
            $table->string('status')->nullable();
            $table->string('production_approval_status')->nullable();
            $table->unsignedBigInteger('ovp_allocated_to')->nullable();
            $table->timestamps();
        });

        Schema::create('outside_office_attendance_requests', function ($table) {
            $table->id();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_forms', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('title', 150);
            $table->string('slug', 180)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_form_fields', function ($table) {
            $table->id();
            $table->foreignId('courier_form_id');
            $table->string('label', 150);
            $table->string('field_key', 150);
            $table->string('field_type', 30);
            $table->string('placeholder', 255)->nullable();
            $table->string('help_text', 255)->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('show_in_list')->default(true);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
            $table->unique(['courier_form_id', 'field_key']);
        });

        Schema::create('couriers', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('courier_code', 50)->unique();
            $table->foreignId('courier_form_id')->nullable();
            $table->string('form_title', 150);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_values', function ($table) {
            $table->id();
            $table->foreignId('courier_id');
            $table->foreignId('courier_form_field_id')->nullable();
            $table->string('field_label', 150);
            $table->string('field_key', 150);
            $table->string('field_type', 30);
            $table->text('value')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'display_name' => 'Super Admin',
        ]);

        foreach (['menuview', 'view', 'create', 'edit', 'delete'] as $action) {
            Permission::create([
                'name' => 'courier.' . $action,
                'guard_name' => 'web',
                'display_name' => ucfirst($action) . ' Courier',
                'module' => 'courier',
            ]);
        }

        $this->adminUser = User::create([
            'name' => 'HR Admin',
            'email' => 'hradmin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ])->refresh();
        $this->adminUser->assignRole($superAdminRole);

        $this->unauthorizedUser = User::create([
            'name' => 'Basic User',
            'email' => 'basic@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ])->refresh();
    }

    public function test_unauthorized_user_cannot_access_courier_routes(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->get(route('hrms.couriers.index'))
            ->assertForbidden();

        $this->actingAs($this->unauthorizedUser)
            ->get(route('hrms.couriers.forms.index'))
            ->assertForbidden();
    }

    public function test_dynamic_courier_form_crud_workflow(): void
    {
        // Create & Index pages render
        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.forms.index'))
            ->assertOk();

        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.forms.create'))
            ->assertOk();

        // Create
        $response = $this->actingAs($this->adminUser)->post(route('hrms.couriers.forms.store'), [
            'title' => 'Outward Courier',
            'description' => 'Dispatch tracking form',
            'is_active' => '1',
            'fields' => [
                [
                    'label' => 'Tracking Number',
                    'field_type' => 'text',
                    'placeholder' => 'Enter AWB number',
                    'is_required' => '1',
                    'show_in_list' => '1',
                ],
                [
                    'label' => 'Courier Partner',
                    'field_type' => 'select',
                    'options_text' => "BlueDart\nDTDC\nDelhivery",
                    'is_required' => '1',
                    'show_in_list' => '1',
                ],
                [
                    'label' => 'Dispatch Date',
                    'field_type' => 'date',
                    'is_required' => '1',
                    'show_in_list' => '1',
                ],
                [
                    'label' => 'Delivery Address',
                    'field_type' => 'textarea',
                    'is_required' => '0',
                    'show_in_list' => '0',
                ],
                [
                    'label' => 'POD Document',
                    'field_type' => 'file',
                    'is_required' => '0',
                    'show_in_list' => '1',
                ],
            ],
        ]);

        $form = CourierForm::with('fields')->first();
        $this->assertNotNull($form);
        $response->assertRedirect(route('hrms.couriers.forms.index'));
        $this->assertSame('Outward Courier', $form->title);
        $this->assertCount(5, $form->fields);

        // Attempting to create a second form redirects to edit and keeps only 1 form
        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.forms.create'))
            ->assertRedirect(route('hrms.couriers.forms.edit', $form));

        $this->actingAs($this->adminUser)
            ->post(route('hrms.couriers.forms.store'), [
                'title' => 'Second Courier Form',
                'is_active' => '1',
                'fields' => [
                    ['label' => 'Extra', 'field_type' => 'text', 'is_required' => '1'],
                ],
            ])
            ->assertRedirect(route('hrms.couriers.forms.edit', $form));

        $this->assertDatabaseCount('courier_forms', 1);

        // Forms index & show & edit pages render the single form
        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.forms.index'))
            ->assertOk()
            ->assertSee('Outward Courier')
            ->assertSee('Tracking Number');

        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.forms.show', $form))
            ->assertOk()
            ->assertSee('Tracking Number')
            ->assertSee('Courier Partner');

        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.forms.edit', $form))
            ->assertOk()
            ->assertSee('Outward Courier');

        // Update: reorder, edit existing, add new field, remove one
        $trackingField = $form->fields->firstWhere('label', 'Tracking Number');
        $partnerField = $form->fields->firstWhere('label', 'Courier Partner');

        $this->actingAs($this->adminUser)->put(route('hrms.couriers.forms.update', $form), [
            'title' => 'Outward Courier V2',
            'description' => 'Updated dispatch form',
            'is_active' => '1',
            'fields' => [
                [
                    'id' => $partnerField->id,
                    'label' => 'Courier Service',
                    'field_type' => 'select',
                    'options_text' => "BlueDart\nDTDC",
                    'is_required' => '1',
                    'show_in_list' => '1',
                ],
                [
                    'id' => $trackingField->id,
                    'label' => 'AWB Number',
                    'field_type' => 'text',
                    'is_required' => '1',
                    'show_in_list' => '1',
                ],
                [
                    'label' => 'Urgency',
                    'field_type' => 'radio',
                    'options_text' => "Normal\nExpress",
                    'is_required' => '1',
                    'show_in_list' => '1',
                ],
            ],
        ])->assertRedirect(route('hrms.couriers.forms.index'));

        $form->refresh()->load('fields');
        $this->assertSame('Outward Courier V2', $form->title);
        $this->assertCount(3, $form->fields);
        $this->assertSame(['Courier Service', 'AWB Number', 'Urgency'], $form->fields->pluck('label')->all());

        // Delete form
        $this->actingAs($this->adminUser)
            ->delete(route('hrms.couriers.forms.destroy', $form))
            ->assertRedirect(route('hrms.couriers.forms.index'));

        $this->assertDatabaseCount('courier_forms', 0);
        $this->assertDatabaseCount('courier_form_fields', 0);
    }

    public function test_dropdown_or_radio_without_options_fails_validation(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('hrms.couriers.forms.store'), [
            'title' => 'Invalid Form',
            'is_active' => '1',
            'fields' => [
                [
                    'label' => 'Courier Partner',
                    'field_type' => 'select',
                    'options_text' => '',
                    'is_required' => '1',
                ],
            ],
        ]);

        $response->assertSessionHasErrors('fields.0.options_text');
        $this->assertDatabaseCount('courier_forms', 0);
    }

    public function test_courier_entry_crud_with_all_dynamic_field_types_and_file_upload(): void
    {
        $form = CourierForm::create([
            'title' => 'Inward Courier',
            'slug' => 'inward-courier',
            'is_active' => true,
            'created_by' => $this->adminUser->id,
        ]);

        $textField = CourierFormField::create([
            'courier_form_id' => $form->id,
            'label' => 'Sender Name',
            'field_key' => 'sender_name_1',
            'field_type' => 'text',
            'is_required' => true,
            'show_in_list' => true,
            'sort_order' => 1,
        ]);

        $textareaField = CourierFormField::create([
            'courier_form_id' => $form->id,
            'label' => 'Remarks',
            'field_key' => 'remarks_2',
            'field_type' => 'textarea',
            'is_required' => false,
            'show_in_list' => true,
            'sort_order' => 2,
        ]);

        $dateField = CourierFormField::create([
            'courier_form_id' => $form->id,
            'label' => 'Received Date',
            'field_key' => 'received_date_3',
            'field_type' => 'date',
            'is_required' => true,
            'show_in_list' => true,
            'sort_order' => 3,
        ]);

        $dropdownField = CourierFormField::create([
            'courier_form_id' => $form->id,
            'label' => 'Courier Partner',
            'field_key' => 'courier_partner_4',
            'field_type' => 'select',
            'options' => ['BlueDart', 'DTDC', 'FedEx'],
            'is_required' => true,
            'show_in_list' => true,
            'sort_order' => 4,
        ]);

        $checkboxField = CourierFormField::create([
            'courier_form_id' => $form->id,
            'label' => 'Package Tags',
            'field_key' => 'package_tags_5',
            'field_type' => 'checkbox',
            'options' => ['Fragile', 'Confidential'],
            'is_required' => false,
            'show_in_list' => true,
            'sort_order' => 5,
        ]);

        $fileField = CourierFormField::create([
            'courier_form_id' => $form->id,
            'label' => 'Receipt Scan',
            'field_key' => 'receipt_scan_6',
            'field_type' => 'file',
            'is_required' => true,
            'show_in_list' => true,
            'sort_order' => 6,
        ]);

        $file = UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf');

        // Create page renders with pre-selected single form
        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.create'))
            ->assertOk()
            ->assertSee('Inward Courier')
            ->assertSee('Sender Name');

        // Store Courier Entry
        $storeResponse = $this->actingAs($this->adminUser)->post(route('hrms.couriers.store'), [
            'courier_form_id' => $form->id,
            'fields' => [
                $textField->id => 'Acme Logistics',
                $textareaField->id => 'Delivered at reception',
                $dateField->id => '2026-11-11',
                $dropdownField->id => 'BlueDart',
                $checkboxField->id => ['Fragile', 'Confidential'],
                $fileField->id => $file,
            ],
        ]);

        $courier = Courier::with('values')->first();
        $this->assertNotNull($courier);
        $this->assertSame('CUR-0001', $courier->courier_code);
        $storeResponse->assertRedirect(route('hrms.couriers.show', $courier));

        $fileValue = $courier->values->firstWhere('courier_form_field_id', $fileField->id);
        $this->assertNotNull($fileValue);
        $this->assertSame('receipt.pdf', $fileValue->file_name);
        Storage::disk('public')->assertExists($fileValue->file_path);

        // Index & Search & Show
        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.index', ['search' => 'Acme Logistics']))
            ->assertOk()
            ->assertSee('CUR-0001')
            ->assertSee('Acme Logistics')
            ->assertSee('BlueDart');

        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.show', $courier))
            ->assertOk()
            ->assertSee('CUR-0001')
            ->assertSee('Acme Logistics')
            ->assertSee('Delivered at reception')
            ->assertSee('Fragile, Confidential')
            ->assertSee('receipt.pdf');

        // Download file
        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.values.download', [$courier, $fileValue]))
            ->assertOk();

        // Edit page renders with existing values
        $this->actingAs($this->adminUser)
            ->get(route('hrms.couriers.edit', $courier))
            ->assertOk()
            ->assertSee('CUR-0001')
            ->assertSee('Acme Logistics');

        // Update Courier Entry (without re-uploading file retains existing file)
        $oldFilePath = $fileValue->file_path;
        $this->actingAs($this->adminUser)->put(route('hrms.couriers.update', $courier), [
            'fields' => [
                $textField->id => 'Acme Corp Updated',
                $textareaField->id => 'Handed to HR',
                $dateField->id => '2026-11-12',
                $dropdownField->id => 'FedEx',
                $checkboxField->id => ['Confidential'],
            ],
        ])->assertRedirect(route('hrms.couriers.show', $courier));

        $courier->refresh()->load('values');
        $this->assertSame('Acme Corp Updated', $courier->values->firstWhere('courier_form_field_id', $textField->id)->value);
        $this->assertSame('FedEx', $courier->values->firstWhere('courier_form_field_id', $dropdownField->id)->value);
        $this->assertSame('Confidential', $courier->values->firstWhere('courier_form_field_id', $checkboxField->id)->value);
        Storage::disk('public')->assertExists($oldFilePath);

        // Update with replacement file deletes old file
        $newFile = UploadedFile::fake()->create('pod_new.pdf', 150, 'application/pdf');
        $this->actingAs($this->adminUser)->put(route('hrms.couriers.update', $courier), [
            'fields' => [
                $textField->id => 'Acme Corp Updated',
                $textareaField->id => 'Handed to HR',
                $dateField->id => '2026-11-12',
                $dropdownField->id => 'FedEx',
                $checkboxField->id => ['Confidential'],
                $fileField->id => $newFile,
            ],
        ])->assertRedirect(route('hrms.couriers.show', $courier));

        $courier->refresh()->load('values');
        $updatedFileValue = $courier->values->firstWhere('courier_form_field_id', $fileField->id);
        $this->assertSame('pod_new.pdf', $updatedFileValue->file_name);
        Storage::disk('public')->assertMissing($oldFilePath);
        Storage::disk('public')->assertExists($updatedFileValue->file_path);

        // Delete Courier Entry cleans up file
        $pathToDelete = $updatedFileValue->file_path;
        $this->actingAs($this->adminUser)
            ->delete(route('hrms.couriers.destroy', $courier))
            ->assertRedirect(route('hrms.couriers.index'));

        $this->assertDatabaseCount('couriers', 0);
        $this->assertDatabaseCount('courier_values', 0);
        Storage::disk('public')->assertMissing($pathToDelete);
    }
}
