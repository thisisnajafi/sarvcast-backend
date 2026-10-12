<?php

namespace App\Support;

/**
 * School-manager permission names shared by the migration, seeder, and panel payload.
 */
final class SchoolAccessCatalog
{
    /**
     * @return list<array{name: string, display_name: string, group: string}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'school_managers.view', 'display_name' => 'مشاهده مدیران مدرسه', 'group' => 'school_managers'],
            ['name' => 'school_managers.create', 'display_name' => 'ایجاد مدیر مدرسه', 'group' => 'school_managers'],
            ['name' => 'school_managers.update', 'display_name' => 'ویرایش مدیر مدرسه', 'group' => 'school_managers'],
            ['name' => 'school_managers.delete', 'display_name' => 'بستن پنل مدیر مدرسه', 'group' => 'school_managers'],
            ['name' => 'school_panel.dashboard.view', 'display_name' => 'مشاهده داشبورد مدرسه', 'group' => 'school_panel'],
            ['name' => 'school_panel.members.view', 'display_name' => 'مشاهده اعضای مدرسه', 'group' => 'school_panel'],
            ['name' => 'school_panel.members.add', 'display_name' => 'افزودن عضو مدرسه', 'group' => 'school_panel'],
            ['name' => 'school_panel.members.remove', 'display_name' => 'حذف عضو مدرسه', 'group' => 'school_panel'],
            ['name' => 'school_panel.activity.view', 'display_name' => 'مشاهده فعالیت اعضای مدرسه', 'group' => 'school_panel'],
            ['name' => 'school_panel.subscriptions.view', 'display_name' => 'مشاهده اشتراک اعضای مدرسه', 'group' => 'school_panel'],
            ['name' => 'school_panel.subscriptions.purchase', 'display_name' => 'خرید اشتراک برای عضو مدرسه', 'group' => 'school_panel'],
            ['name' => 'school_panel.premium.grant', 'display_name' => 'اعطای پریمیوم رایگان مدرسه', 'group' => 'school_panel'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function panelPermissionNames(): array
    {
        return array_values(array_map(
            static fn (array $row): string => $row['name'],
            array_filter(
                self::definitions(),
                static fn (array $row): bool => $row['group'] === 'school_panel',
            ),
        ));
    }

    /**
     * @return list<string>
     */
    public static function managerAdminPermissionNames(): array
    {
        return array_values(array_map(
            static fn (array $row): string => $row['name'],
            array_filter(
                self::definitions(),
                static fn (array $row): bool => $row['group'] === 'school_managers',
            ),
        ));
    }
}
