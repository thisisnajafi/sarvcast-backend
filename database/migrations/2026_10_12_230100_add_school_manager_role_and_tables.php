<?php

use App\Support\SchoolAccessCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSchoolManagerRole();
        $this->createTables();
        $this->seedAccess();
    }

    public function down(): void
    {
        Schema::dropIfExists('school_memberships');
        Schema::dropIfExists('school_manager_profiles');

        $names = array_map(
            static fn (array $row): string => $row['name'],
            SchoolAccessCatalog::definitions(),
        );
        $permissionIds = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permission')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }
        $roleId = DB::table('roles')->where('name', 'school_manager')->value('id');
        if ($roleId) {
            DB::table('user_role')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::table('users')->where('role', 'school_manager')->update(['role' => 'basic']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('parent', 'child', 'admin', 'basic', 'super_admin', 'voice_actor', 'writer', 'head_writer', 'image_assistant') DEFAULT 'basic'");
    }

    private function addSchoolManagerRole(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('parent', 'child', 'admin', 'basic', 'super_admin', 'voice_actor', 'writer', 'head_writer', 'image_assistant', 'school_manager') DEFAULT 'basic'");

            return;
        }

        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('basic')->change();
        });
    }

    private function createTables(): void
    {
        if (! Schema::hasTable('school_manager_profiles')) {
            Schema::create('school_manager_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('member_limit')->default(0);
                $table->string('label', 120)->nullable();
                $table->string('status', 20)->default('active');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('status');
            });
        }

        if (! Schema::hasTable('school_memberships')) {
            Schema::create('school_memberships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_manager_profile_id')->constrained('school_manager_profiles')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('active_user_id')->nullable();
                $table->foreignId('attached_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('detached_at')->nullable();
                $table->foreignId('detached_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique('active_user_id');
                $table->index(['school_manager_profile_id', 'detached_at']);
                $table->index('user_id');
            });
        }
    }

    private function seedAccess(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_permission')) {
            return;
        }

        $now = now();
        $permissionIdsByName = [];

        foreach (SchoolAccessCatalog::definitions() as $definition) {
            $existing = DB::table('permissions')->where('name', $definition['name'])->value('id');
            if ($existing) {
                $permissionIdsByName[$definition['name']] = (int) $existing;

                continue;
            }

            $permissionIdsByName[$definition['name']] = (int) DB::table('permissions')->insertGetId([
                'name' => $definition['name'],
                'display_name' => $definition['display_name'],
                'description' => null,
                'group' => $definition['group'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $schoolRoleId = DB::table('roles')->where('name', 'school_manager')->value('id');
        if (! $schoolRoleId) {
            $schoolRoleId = DB::table('roles')->insertGetId([
                'name' => 'school_manager',
                'display_name' => 'مدیر مدرسه',
                'description' => 'مدیریت اعضای پنل مدرسه تا سقف تعیین‌شده',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (SchoolAccessCatalog::panelPermissionNames() as $name) {
            $this->attachPermission((int) $schoolRoleId, $permissionIdsByName[$name], $now);
        }

        $superAdminRoleId = DB::table('roles')->where('name', 'super_admin')->value('id');
        if ($superAdminRoleId) {
            foreach (SchoolAccessCatalog::managerAdminPermissionNames() as $name) {
                $this->attachPermission((int) $superAdminRoleId, $permissionIdsByName[$name], $now);
            }
        }
    }

    private function attachPermission(int $roleId, int $permissionId, $now): void
    {
        $exists = DB::table('role_permission')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('role_permission')->insert([
            'role_id' => $roleId,
            'permission_id' => $permissionId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
