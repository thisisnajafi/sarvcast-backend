<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $definitions = [
            ['name' => 'support.view',   'display_name' => 'مشاهده تیکت‌های پشتیبانی', 'group' => 'support'],
            ['name' => 'support.reply',  'display_name' => 'پاسخ به تیکت پشتیبانی',    'group' => 'support'],
            ['name' => 'support.close',  'display_name' => 'بستن تیکت پشتیبانی',        'group' => 'support'],
            ['name' => 'support.assign', 'display_name' => 'تخصیص تیکت پشتیبانی',      'group' => 'support'],
        ];

        $permissionIds = [];
        foreach ($definitions as $definition) {
            $permission = Permission::firstOrCreate(
                ['name' => $definition['name']],
                $definition
            );
            $permissionIds[] = $permission->id;
        }

        // Admin gets all support permissions
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Super admin gets everything (via Permission::all sync elsewhere, but add here too)
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }

    public function down(): void
    {
        // Permissions are shared; leave rows in place on rollback.
    }
};
