<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    protected array $permissionsData = [
        'courier' => [
            'menuview' => ['display' => 'Menuview Courier', 'desc' => 'Allows users to view Courier menu item in HRMS module.'],
            'view'     => ['display' => 'View Courier', 'desc' => 'Allows users to view Courier forms and courier entries.'],
            'create'   => ['display' => 'Create Courier', 'desc' => 'Allows users to create dynamic Courier forms and courier entries.'],
            'edit'     => ['display' => 'Edit Courier', 'desc' => 'Allows users to edit dynamic Courier forms and courier entries.'],
            'delete'   => ['display' => 'Delete Courier', 'desc' => 'Allows users to delete dynamic Courier forms and courier entries.'],
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('courier_forms')) {
            Schema::create('courier_forms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('title', 150);
                $table->string('slug', 180)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('courier_form_fields')) {
            Schema::create('courier_form_fields', function (Blueprint $table) {
                $table->id();
                $table->foreignId('courier_form_id')->constrained('courier_forms')->cascadeOnDelete();
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

                $table->unique(['courier_form_id', 'field_key'], 'cff_form_key_unique');
            });
        }

        if (! Schema::hasTable('couriers')) {
            Schema::create('couriers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('courier_code', 50)->unique();
                $table->foreignId('courier_form_id')->nullable()->constrained('courier_forms')->nullOnDelete();
                $table->string('form_title', 150);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('courier_values')) {
            Schema::create('courier_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('courier_id')->constrained('couriers')->cascadeOnDelete();
                $table->foreignId('courier_form_field_id')->nullable()->constrained('courier_form_fields')->nullOnDelete();
                $table->string('field_label', 150);
                $table->string('field_key', 150);
                $table->string('field_type', 30);
                $table->text('value')->nullable();
                $table->string('file_path', 500)->nullable();
                $table->string('file_name', 255)->nullable();
                $table->timestamps();
            });
        }

        $this->seedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_values');
        Schema::dropIfExists('couriers');
        Schema::dropIfExists('courier_form_fields');
        Schema::dropIfExists('courier_forms');

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where('module', 'courier')
                ->delete();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $timestamp = now();
        $records = [];
        $permissionNames = [];

        foreach ($this->permissionsData as $module => $actions) {
            foreach ($actions as $action => $meta) {
                $name = $module . '.' . $action;
                $permissionNames[] = $name;
                $records[] = [
                    'name'         => $name,
                    'guard_name'   => 'web',
                    'display_name' => $meta['display'],
                    'module'       => $module,
                    'description'  => $meta['desc'],
                    'company_id'   => null,
                    'created_at'   => $timestamp,
                    'updated_at'   => $timestamp,
                ];
            }
        }

        DB::table('permissions')->upsert(
            $records,
            ['name', 'guard_name'],
            ['display_name', 'module', 'description', 'company_id', 'updated_at']
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $createdPermissions = Permission::whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();

        if ($createdPermissions->isEmpty()) {
            return;
        }

        $adminRoles = Role::where(function ($q) {
            $q->where('name', 'super_admin')
                ->orWhere('name', 'like', '%company_admin')
                ->orWhere('name', 'like', '%admin');
        })->get();

        foreach ($adminRoles as $role) {
            try {
                $role->givePermissionTo($createdPermissions);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Also grant to roles/users that already have HRMS Form Builder / Visitor Management / Assets menu access
        $hrmsPermissionNames = ['dynamic_forms.menuview', 'visitor_management.menuview', 'assets.menuview'];
        Role::query()
            ->whereHas('permissions', fn ($q) => $q->whereIn('name', $hrmsPermissionNames))
            ->get()
            ->each(function (Role $role) use ($createdPermissions) {
                try {
                    $role->givePermissionTo($createdPermissions);
                } catch (\Throwable $e) {
                    report($e);
                }
            });

        User::query()
            ->with('roles')
            ->chunkById(100, function ($users) use ($permissionNames, $hrmsPermissionNames) {
                foreach ($users as $user) {
                    try {
                        $hasHrmsAccess = false;
                        foreach ($hrmsPermissionNames as $perm) {
                            if ($user->hasPermissionTo($perm)) {
                                $hasHrmsAccess = true;
                                break;
                            }
                        }

                        if ($hasHrmsAccess) {
                            foreach ($permissionNames as $permName) {
                                if (! $user->hasPermissionTo($permName)) {
                                    $user->givePermissionTo($permName);
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
