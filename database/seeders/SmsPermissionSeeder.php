<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class SmsPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'sms.templates.read', 'display_name' => 'مشاهده قالب‌های پیامک', 'group' => 'sms'],
            ['name' => 'sms.templates.create', 'display_name' => 'ایجاد قالب پیامک', 'group' => 'sms'],
            ['name' => 'sms.templates.update', 'display_name' => 'ویرایش قالب پیامک', 'group' => 'sms'],
            ['name' => 'sms.templates.delete', 'display_name' => 'حذف قالب پیامک', 'group' => 'sms'],
            ['name' => 'sms.templates.bulk', 'display_name' => 'عملیات گروهی قالب‌های پیامک', 'group' => 'sms'],
            ['name' => 'sms.templates.export', 'display_name' => 'خروجی قالب‌های پیامک', 'group' => 'sms'],
            ['name' => 'sms.templates.send_test', 'display_name' => 'ارسال تست قالب پیامک', 'group' => 'sms'],
            ['name' => 'sms.campaigns.read', 'display_name' => 'مشاهده کمپین‌های پیامک', 'group' => 'sms'],
            ['name' => 'sms.campaigns.create', 'display_name' => 'ایجاد کمپین پیامک', 'group' => 'sms'],
            ['name' => 'sms.campaigns.update', 'display_name' => 'ویرایش کمپین پیامک', 'group' => 'sms'],
            ['name' => 'sms.campaigns.delete', 'display_name' => 'حذف کمپین پیامک', 'group' => 'sms'],
            ['name' => 'sms.campaigns.send', 'display_name' => 'ارسال کمپین پیامک', 'group' => 'sms'],
            ['name' => 'sms.campaigns.export', 'display_name' => 'خروجی کمپین‌های پیامک', 'group' => 'sms'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->permissions()->syncWithoutDetaching(
                Permission::where('group', 'sms')->pluck('id')
            );
        }

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->syncWithoutDetaching(
                Permission::whereIn('name', [
                    'sms.templates.read',
                    'sms.templates.create',
                    'sms.templates.update',
                    'sms.templates.send_test',
                    'sms.campaigns.read',
                    'sms.campaigns.create',
                    'sms.campaigns.send',
                ])->pluck('id')
            );
        }
    }
}
