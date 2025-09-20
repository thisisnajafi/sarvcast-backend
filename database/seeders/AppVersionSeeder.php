<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppVersion;
use Carbon\Carbon;

class AppVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $versions = [
            [
                'version' => '1.0.0',
                'build_number' => '100',
                'platform' => 'all',
                'update_type' => 'optional',
                'title' => 'نسخه اولیه',
                'description' => 'اولین نسخه از اپلیکیشن سروکست',
                'changelog' => '• راه‌اندازی اولیه اپلیکیشن
• پخش داستان‌ها و اپیزودها
• سیستم اشتراک
• پروفایل کاربری',
                'update_notes' => 'این نسخه پایه اپلیکیشن است و تمام ویژگی‌های اصلی را شامل می‌شود.',
                'download_url' => 'https://play.google.com/store/apps/details?id=com.sarvcast.app',
                'minimum_os_version' => '7.0',
                'compatibility' => [
                    'min_android_version' => '7.0',
                    'min_ios_version' => '12.0',
                    'supported_devices' => ['phone', 'tablet']
                ],
                'is_active' => true,
                'is_latest' => false,
                'release_date' => Carbon::now()->subDays(30),
                'priority' => 1,
                'metadata' => [
                    'release_notes' => 'Initial release',
                    'features' => ['stories', 'episodes', 'subscription', 'profile']
                ],
            ],
            [
                'version' => '1.1.0',
                'build_number' => '110',
                'platform' => 'all',
                'update_type' => 'optional',
                'title' => 'بهبودهای عملکرد',
                'description' => 'بهبودهای عملکرد و رفع مشکلات',
                'changelog' => '• بهبود سرعت پخش
• رفع مشکلات دانلود
• بهبود رابط کاربری
• اضافه شدن قابلیت اشتراک‌گذاری',
                'update_notes' => 'این به‌روزرسانی شامل بهبودهای مهم عملکرد و رفع مشکلات گزارش شده است.',
                'download_url' => 'https://play.google.com/store/apps/details?id=com.sarvcast.app',
                'minimum_os_version' => '7.0',
                'compatibility' => [
                    'min_android_version' => '7.0',
                    'min_ios_version' => '12.0',
                    'supported_devices' => ['phone', 'tablet']
                ],
                'is_active' => true,
                'is_latest' => false,
                'release_date' => Carbon::now()->subDays(15),
                'priority' => 2,
                'metadata' => [
                    'release_notes' => 'Performance improvements',
                    'features' => ['performance', 'sharing', 'ui_improvements']
                ],
            ],
            [
                'version' => '1.2.0',
                'build_number' => '120',
                'platform' => 'all',
                'update_type' => 'forced',
                'title' => 'به‌روزرسانی امنیتی مهم',
                'description' => 'به‌روزرسانی امنیتی مهم و ویژگی‌های جدید',
                'changelog' => '• رفع مشکلات امنیتی مهم
• اضافه شدن سیستم نظرات
• بهبود سیستم اعلان‌ها
• اضافه شدن قابلیت دانلود آفلاین',
                'update_notes' => 'این به‌روزرسانی شامل رفع مشکلات امنیتی مهم است و به‌روزرسانی آن اجباری است.',
                'download_url' => 'https://play.google.com/store/apps/details?id=com.sarvcast.app',
                'minimum_os_version' => '7.0',
                'compatibility' => [
                    'min_android_version' => '7.0',
                    'min_ios_version' => '12.0',
                    'supported_devices' => ['phone', 'tablet']
                ],
                'is_active' => true,
                'is_latest' => true,
                'release_date' => Carbon::now()->subDays(5),
                'force_update_date' => Carbon::now()->subDays(2),
                'priority' => 10,
                'metadata' => [
                    'release_notes' => 'Security update',
                    'features' => ['security', 'comments', 'notifications', 'offline_download'],
                    'security_level' => 'high'
                ],
            ],
            [
                'version' => '1.2.1-android',
                'build_number' => '121',
                'platform' => 'android',
                'update_type' => 'optional',
                'title' => 'رفع مشکلات اندروید',
                'description' => 'رفع مشکلات خاص پلتفرم اندروید',
                'changelog' => '• رفع مشکل پخش در اندروید 11
• بهبود عملکرد باتری
• رفع مشکل اعلان‌ها در برخی دستگاه‌ها',
                'update_notes' => 'این به‌روزرسانی مخصوص کاربران اندروید است.',
                'download_url' => 'https://play.google.com/store/apps/details?id=com.sarvcast.app',
                'minimum_os_version' => '7.0',
                'compatibility' => [
                    'min_android_version' => '7.0',
                    'supported_devices' => ['phone', 'tablet']
                ],
                'is_active' => true,
                'is_latest' => false,
                'release_date' => Carbon::now()->subDays(2),
                'priority' => 3,
                'metadata' => [
                    'release_notes' => 'Android fixes',
                    'features' => ['android_fixes', 'battery_optimization']
                ],
            ],
            [
                'version' => '1.2.1-ios',
                'build_number' => '121',
                'platform' => 'ios',
                'update_type' => 'optional',
                'title' => 'رفع مشکلات iOS',
                'description' => 'رفع مشکلات خاص پلتفرم iOS',
                'changelog' => '• رفع مشکل پخش در iOS 15
• بهبود عملکرد در iPhone 13
• رفع مشکل اعلان‌ها در iOS',
                'update_notes' => 'این به‌روزرسانی مخصوص کاربران iOS است.',
                'download_url' => 'https://apps.apple.com/app/sarvcast/id123456789',
                'minimum_os_version' => '12.0',
                'compatibility' => [
                    'min_ios_version' => '12.0',
                    'supported_devices' => ['phone', 'tablet']
                ],
                'is_active' => true,
                'is_latest' => false,
                'release_date' => Carbon::now()->subDays(2),
                'priority' => 3,
                'metadata' => [
                    'release_notes' => 'iOS fixes',
                    'features' => ['ios_fixes', 'iphone13_optimization']
                ],
            ],
        ];

        foreach ($versions as $versionData) {
            AppVersion::create($versionData);
        }
    }
}