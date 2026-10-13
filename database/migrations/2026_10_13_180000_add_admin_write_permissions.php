<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $definitions = [
            ['name' => 'users.create', 'display_name' => 'ایجاد کاربر', 'group' => 'user_management'],
            ['name' => 'users.update', 'display_name' => 'ویرایش کاربر', 'group' => 'user_management'],
            ['name' => 'users.delete', 'display_name' => 'حذف کاربر', 'group' => 'user_management'],
            ['name' => 'stories.create', 'display_name' => 'ایجاد داستان', 'group' => 'stories'],
            ['name' => 'stories.update', 'display_name' => 'ویرایش داستان', 'group' => 'stories'],
            ['name' => 'stories.delete', 'display_name' => 'حذف داستان', 'group' => 'stories'],
            ['name' => 'roles.update', 'display_name' => 'ویرایش نقش', 'group' => 'role_management'],
        ];

        $permissionIds = [];
        foreach ($definitions as $definition) {
            $permission = Permission::firstOrCreate(
                ['name' => $definition['name']],
                $definition
            );
            $permissionIds[] = $permission->id;
        }

        foreach (['admin', 'super_admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        }
    }

    public function down(): void
    {
        // Permissions are shared; leave rows in place on rollback.
    }
};
