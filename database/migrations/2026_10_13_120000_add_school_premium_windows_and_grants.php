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
        $this->widenSubscriptionColumns();
        $this->createTables();
        $this->ensureGrantPermission();
    }

    public function down(): void
    {
        Schema::dropIfExists('school_premium_grants');
        Schema::dropIfExists('school_premium_windows');

        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        DB::table('subscriptions')->where('type', 'like', 'school_%')->update(['type' => '1month']);
        DB::table('subscriptions')->where('billing_platform', 'school_manager')->update(['billing_platform' => 'website']);
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN type ENUM('1month', '3months', '6months', '1year') NOT NULL");
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN billing_platform ENUM('website', 'cafebazaar', 'myket') NOT NULL DEFAULT 'website'");
    }

    private function widenSubscriptionColumns(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE subscriptions MODIFY COLUMN type VARCHAR(32) NOT NULL');
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN billing_platform VARCHAR(32) NOT NULL DEFAULT 'website'");

            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('type', 32)->change();
            $table->string('billing_platform', 32)->default('website')->change();
        });
    }

    private function createTables(): void
    {
        if (! Schema::hasTable('school_premium_windows')) {
            Schema::create('school_premium_windows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_manager_profile_id')->nullable()->constrained('school_manager_profiles')->nullOnDelete();
                $table->unsignedTinyInteger('duration_months');
                $table->unsignedInteger('seat_limit');
                $table->timestamp('expires_at')->index();
                $table->string('status', 20)->default('open');
                $table->unsignedBigInteger('active_profile_id')->nullable()->unique();
                $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['school_manager_profile_id', 'status']);
            });
        }

        if (! Schema::hasTable('school_premium_grants')) {
            Schema::create('school_premium_grants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_premium_window_id')->constrained('school_premium_windows')->restrictOnDelete();
                $table->foreignId('school_manager_profile_id')->nullable()->constrained('school_manager_profiles')->nullOnDelete();
                $table->foreignId('school_membership_id')->nullable()->constrained('school_memberships')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
                $table->unsignedTinyInteger('duration_months');
                $table->timestamp('premium_ends_at');
                $table->timestamp('previous_end_date')->nullable();
                $table->string('status', 20)->default('active');
                $table->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
                $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('revoked_at')->nullable();
                $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['school_premium_window_id', 'user_id']);
                $table->index(['user_id', 'status']);
                $table->index(['status', 'premium_ends_at']);
                $table->index('school_manager_profile_id');
            });
        }
    }

    /**
     * Phase 02 already inserts the catalog. Repeat it so a database that migrated
     * before school_panel.premium.grant existed still receives that row.
     */
    private function ensureGrantPermission(): void
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
        if ($schoolRoleId && isset($permissionIdsByName['school_panel.premium.grant'])) {
            $this->attachPermission((int) $schoolRoleId, $permissionIdsByName['school_panel.premium.grant'], $now);
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
