<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

/**
 * Idempotent roles/permissions seeder.
 * Existing permission/role rows are skipped (firstOrCreate).
 * Missing required permissions are attached without detaching extras.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
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
            ['name' => 'media.read', 'display_name' => 'مشاهده کتابخانه رسانه', 'group' => 'media_library'],
            ['name' => 'media.create', 'display_name' => 'آپلود رسانه', 'group' => 'media_library'],
            ['name' => 'media.update', 'display_name' => 'ویرایش رسانه', 'group' => 'media_library'],
            ['name' => 'media.delete', 'display_name' => 'حذف رسانه', 'group' => 'media_library'],
            ['name' => 'audit.view', 'display_name' => 'مشاهده گزارش فعالیت', 'group' => 'audit'],
            ['name' => 'audit.export', 'display_name' => 'خروجی گزارش فعالیت', 'group' => 'audit'],
            ['name' => 'team_members.view', 'display_name' => 'مشاهده تیم مانجی', 'group' => 'team_members'],
            ['name' => 'team_members.create', 'display_name' => 'افزودن عضو تیم', 'group' => 'team_members'],
            ['name' => 'team_members.update', 'display_name' => 'ویرایش عضو تیم', 'group' => 'team_members'],
            ['name' => 'team_members.delete', 'display_name' => 'حذف عضو تیم', 'group' => 'team_members'],
            ['name' => 'stories.read', 'display_name' => 'مشاهده داستان‌های اختصاصی', 'group' => 'stories'],
            ['name' => 'story_editor.read', 'display_name' => 'مشاهده ویرایشگر داستان', 'group' => 'story_editor'],
            ['name' => 'story_editor.update', 'display_name' => 'ویرایش اسکریپت داستان', 'group' => 'story_editor'],
        ];

        $createdPermissions = 0;
        $skippedPermissions = 0;
        foreach ($permissions as $permission) {
            $model = Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
            if ($model->wasRecentlyCreated) {
                $createdPermissions++;
            } else {
                $skippedPermissions++;
            }
        }

        $this->command?->info("Permissions: created={$createdPermissions}, skipped_existing={$skippedPermissions}");

        [$superAdminRole, $superCreated] = $this->firstOrCreateRole('super_admin', [
            'display_name' => 'مدیر کل',
            'description' => 'دسترسی کامل به تمام بخش‌های سیستم',
        ]);
        // Ensure super_admin has every permission (additive).
        $superAdminRole->permissions()->syncWithoutDetaching(Permission::all()->pluck('id'));

        [$adminRole, $adminCreated] = $this->firstOrCreateRole('admin', [
            'display_name' => 'مدیر',
            'description' => 'دسترسی به مدیریت محتوا و کاربران',
        ]);
        $adminPermissionIds = Permission::whereIn('group', [
            'dashboard', 'coin_management', 'coupon_management',
            'payment_management', 'partner_management', 'analytics',
            'user_management', 'media_library', 'team_members', 'stories', 'story_editor',
        ])->pluck('id');
        $adminRole->permissions()->syncWithoutDetaching($adminPermissionIds);

        $auditView = Permission::where('name', 'audit.view')->first();
        if ($auditView) {
            $adminRole->permissions()->syncWithoutDetaching([$auditView->id]);
        }

        [$voiceActorRole, $voiceCreated] = $this->firstOrCreateRole('voice_actor', [
            'display_name' => 'صداپیشه',
            'description' => 'مشاهده داستان‌های اختصاص‌یافته؛ نویسندگان می‌توانند اسکریپت را ویرایش کنند',
        ]);
        $voiceActorRole->permissions()->syncWithoutDetaching(
            Permission::whereIn('name', [
                'dashboard.view',
                'stories.read',
                'story_editor.read',
                'story_editor.update',
            ])->pluck('id')
        );

        $this->command?->info(sprintf(
            'Roles: super_admin=%s, admin=%s, voice_actor=%s',
            $superCreated ? 'created' : 'exists(skipped)',
            $adminCreated ? 'created' : 'exists(skipped)',
            $voiceCreated ? 'created' : 'exists(skipped)'
        ));

        $abolfazl = User::where('phone_number', '09136708883')->first();
        if ($abolfazl) {
            if (! $abolfazl->hasRole('super_admin')) {
                $abolfazl->assignRole($superAdminRole);
                $this->command?->info('Super Admin role assigned to Abolfazl (09136708883)');
            } else {
                $this->command?->info('Abolfazl already has Super Admin role (skipped)');
            }
        } else {
            $this->command?->warn('User with phone number 09136708883 not found. Please create the user first.');
        }
    }

    /**
     * @param  array{display_name: string, description: string}  $attributes
     * @return array{0: Role, 1: bool}
     */
    private function firstOrCreateRole(string $name, array $attributes): array
    {
        $role = Role::firstOrCreate(['name' => $name], $attributes);

        return [$role, $role->wasRecentlyCreated];
    }
}
