<?php

namespace App\Http\Middleware;

use App\Models\SchoolManagerProfile;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSchoolPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('sanctum')->user();
        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'احراز هویت الزامی است.',
                'error' => 'UNAUTHENTICATED',
            ], 401);
        }

        if (! $user->isSchoolManager()) {
            return response()->json([
                'success' => false,
                'message' => 'این بخش فقط برای مدیر مدرسه است.',
                'error' => 'FORBIDDEN',
            ], 403);
        }

        if (! in_array($user->status, User::loginAllowedStatuses(), true)) {
            return response()->json([
                'success' => false,
                'message' => 'حساب کاربری شما غیرفعال است.',
                'error' => 'ACCOUNT_INACTIVE',
            ], 403);
        }

        $profile = SchoolManagerProfile::query()->where('user_id', $user->id)->first();
        if (! $profile || $profile->status === SchoolManagerProfile::STATUS_DELETED) {
            return response()->json([
                'success' => false,
                'message' => 'پنل مدرسه برای این حساب فعال نیست.',
                'error' => 'PANEL_MISSING',
            ], 403);
        }

        if ($profile->status !== SchoolManagerProfile::STATUS_ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'پنل مدرسه شما تعلیق شده است.',
                'error' => 'PANEL_SUSPENDED',
            ], 403);
        }

        $request->attributes->set('schoolManagerProfile', $profile);

        return $next($request);
    }
}
