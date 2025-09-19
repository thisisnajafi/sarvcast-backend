<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create basic permissions
        $permissions = [
            ['name' => 'dashboard.view', 'display_name' => 'مشاهده داشبورد', 'group' => 'dashboard'],
            ['name' => 'coins.view', 'display_name' => 'مشاهده مدیریت سکه', 'group' => 'coin_management'],
            ['name' => 'coins.award', 'display_name' => 'اعطای سکه', 'group' => 'coin_management'],
            ['name' => 'coupons.view', 'display_name' => 'مشاهده کدهای کوپن', 'group' => 'coupon_management'],
            ['name' => 'coupons.create', 'display_name' => 'ایجاد کد کوپن', 'group' => 'coupon_management'],
            ['name' => 'payments.view', 'display_name' => 'مشاهده پرداخت‌ها', 'group' => 'payment_management'],
            ['name' => 'payments.process', 'display_name' => 'پردازش پرداخت', 'group' => 'payment_management'],
            ['name' => 'partners.view', 'display_name' => 'مشاهده شرکا', 'group' => 'partner_management'],
            ['name' => 'analytics.view', 'display_name' => 'مشاهده آمار', 'group' => 'analytics'],
            ['name' => 'users.view', 'display_name' => 'مشاهده کاربران', 'group' => 'user_management'],
            ['name' => 'roles.view', 'display_name' => 'مشاهده نقش‌ها', 'group' => 'role_management'],
            ['name' => 'roles.create', 'display_name' => 'ایجاد نقش', 'group' => 'role_management'],
            ['name' => 'roles.edit', 'display_name' => 'ویرایش نقش', 'group' => 'role_management'],
            ['name' => 'roles.delete', 'display_name' => 'حذف نقش', 'group' => 'role_management'],
            ['name' => 'roles.assign', 'display_name' => 'اختصاص نقش', 'group' => 'role_management'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        // Create Super Admin role with all permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], [
            'display_name' => 'مدیر کل',
            'description' => 'دسترسی کامل به تمام بخش‌های سیستم'
        ]);
        $superAdminRole->permissions()->sync(Permission::all()->pluck('id'));

        // Create regular Admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin'], [
            'display_name' => 'مدیر',
            'description' => 'دسترسی به مدیریت محتوا و کاربران'
        ]);
        $adminRole->permissions()->sync(Permission::whereIn('group', [
            'dashboard', 'coin_management', 'coupon_management', 
            'payment_management', 'partner_management', 'analytics',
            'user_management'
        ])->pluck('id'));

        // Assign Super Admin role to Abolfazl
        $abolfazl = User::where('phone_number', '09136708883')->first();
        if ($abolfazl) {
            $abolfazl->assignRole($superAdminRole);
            $this->command->info('Super Admin role assigned to Abolfazl (09136708883)');
        } else {
            $this->command->warn('User with phone number 09136708883 not found. Please create the user first.');
        }
    }
}