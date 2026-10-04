<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'plan_slug' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();
        $result = $this->couponService->validateCouponCode(
            $validated['code'],
            $user,
            $validated['amount'],
            $validated['plan_slug'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Prepare a CafeBazaar dynamic-price token for a Manji coupon + SKU.
     *
     * POST /api/v1/coupons/cafebazaar/prepare
     */
    public function prepareCafeBazaarCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'product_id' => 'required|string|max:100',
            'plan_slug' => 'nullable|string|max:50',
        ]);

        $result = $this->couponService->prepareCafeBazaarCoupon(
            Auth::user(),
            $validated['code'],
            $validated['product_id'],
            $validated['plan_slug'] ?? null
        );

        $status = 400;
        if ($result['success'] ?? false) {
            $status = 200;
        } elseif (($result['error_code'] ?? null) === 'DYNAMIC_PRICE_NOT_CONFIGURED') {
            $status = 503;
        }

        return response()->json($result, $status);
    }

    public function useCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'subscription_id' => 'required|exists:subscriptions,id',
        ]);

        $user = Auth::user();
        $subscription = \App\Models\Subscription::findOrFail($validated['subscription_id']);

        // Verify subscription belongs to user
        if ($subscription->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'اشتراک متعلق به شما نیست'
            ], 403);
        }

        $result = $this->couponService->useCouponCode(
            $validated['code'],
            $user,
            $subscription
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function getMyCoupons(): JsonResponse
    {
        $user = Auth::user();
        
        // Get coupons created by this user (if they're a partner)
        $partner = \App\Models\AffiliatePartner::where('user_id', $user->id)->first();
        
        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'شما شریک نیستید'
            ], 403);
        }

        $result = $this->couponService->getCouponCodes([
            'partner_id' => $partner->id
        ]);

        return response()->json($result);
    }

    public function getCouponUsage(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Get partner
        $partner = \App\Models\AffiliatePartner::where('user_id', $user->id)->first();
        
        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'شما شریک نیستید'
            ], 403);
        }

        $filters = $request->only(['status', 'date_from', 'date_to']);
        $filters['partner_id'] = $partner->id;

        $result = $this->couponService->getCouponUsage($filters);

        return response()->json($result);
    }

    public function getCouponStatistics(): JsonResponse
    {
        $user = Auth::user();
        
        // Get partner
        $partner = \App\Models\AffiliatePartner::where('user_id', $user->id)->first();
        
        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'شما شریک نیستید'
            ], 403);
        }

        $result = $this->couponService->getCouponStatistics();

        return response()->json($result);
    }

    // Admin methods
    public function createCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:coupon_codes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => ['required', Rule::in(['percentage', 'fixed_amount', 'free_trial'])],
            'discount_value' => 'required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'partner_type' => ['required', Rule::in(['influencer', 'teacher', 'partner', 'promotional'])],
            'partner_id' => 'nullable|exists:affiliate_partners,id',
            'usage_limit' => 'nullable|integer|min:1',
            'user_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'applicable_plans' => 'nullable|array',
            'metadata' => 'nullable|array',
        ]);

        $validated['created_by'] = Auth::id();

        $result = $this->couponService->createCouponCode($validated);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    public function getCoupons(Request $request): JsonResponse
    {
        $filters = $request->only(['partner_type', 'partner_id', 'is_active', 'search']);
        
        $result = $this->couponService->getCouponCodes($filters);

        return response()->json($result);
    }

    public function getAllCouponUsage(Request $request): JsonResponse
    {
        $filters = $request->only(['coupon_code_id', 'partner_id', 'status', 'date_from', 'date_to']);
        
        $result = $this->couponService->getCouponUsage($filters);

        return response()->json($result);
    }

    public function getGlobalStatistics(): JsonResponse
    {
        $result = $this->couponService->getCouponStatistics();

        return response()->json($result);
    }
}
