<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminApiResponse;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppSettingController extends Controller
{
    /**
     * @return array<int, array{key: string, type: string, value: mixed}>
     */
    private function knownKeys(): array
    {
        return [
            [
                'key' => AppSetting::PUBLIC_VOICE_ACTORS_REQUIRE_PHOTO,
                'type' => 'bool',
                'value' => AppSetting::getBool(AppSetting::PUBLIC_VOICE_ACTORS_REQUIRE_PHOTO, false),
            ],
            [
                'key' => AppSetting::MAINTENANCE_MODE,
                'type' => 'bool',
                'value' => AppSetting::getBool(AppSetting::MAINTENANCE_MODE, false),
            ],
            [
                'key' => AppSetting::SITE_ANNOUNCEMENT,
                'type' => 'string',
                'value' => AppSetting::getValue(AppSetting::SITE_ANNOUNCEMENT, ''),
            ],
        ];
    }

    public function apiIndex()
    {
        return AdminApiResponse::success([
            'settings' => $this->knownKeys(),
        ]);
    }

    public function apiUpdate(Request $request)
    {
        $known = collect($this->knownKeys())->pluck('key')->all();

        $validator = validator($request->all(), [
            'key' => ['required', 'string', Rule::in($known)],
            'value' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'error' => 'VALIDATION_ERROR',
            ], 422);
        }

        $key = (string) $request->input('key');
        $value = $request->input('value');

        $meta = collect($this->knownKeys())->firstWhere('key', $key);
        if (($meta['type'] ?? '') === 'bool') {
            AppSetting::setBool($key, filter_var($value, FILTER_VALIDATE_BOOLEAN));
        } else {
            AppSetting::setValue($key, $value === null ? '' : (string) $value);
        }

        return AdminApiResponse::success([
            'key' => $key,
            'value' => ($meta['type'] ?? '') === 'bool'
                ? AppSetting::getBool($key, false)
                : AppSetting::getValue($key, ''),
        ], 'تنظیم ذخیره شد.');
    }
}
