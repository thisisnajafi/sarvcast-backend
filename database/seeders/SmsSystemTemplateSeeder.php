<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsSystemTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $bodyId = (int) config('services.melipayamk.templates.verification', 485459);

        SmsTemplate::updateOrCreate(
            ['slug' => 'verification-otp'],
            [
                'name' => 'کد تایید ورود',
                'melipayamak_body_id' => $bodyId,
                'preview_text' => 'کد ورود شما: {0} این کد 5 دقیقه اعتبار دارد مانجی',
                'parameters' => [
                    [
                        'index' => 0,
                        'label' => 'کد تایید',
                        'source' => 'custom',
                        'fallback' => '',
                    ],
                ],
                'category' => SmsTemplate::CATEGORY_SYSTEM,
                'description' => 'قالب سیستمی OTP — از پنل ملی‌پیامک همگام می‌شود.',
                'is_active' => true,
            ]
        );

        $this->command?->info('System SMS template verification-otp seeded (bodyId: '.$bodyId.').');
    }
}
