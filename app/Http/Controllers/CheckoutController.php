<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Subscription;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    /**
     * Show the public checkout page where users select a plan,
     * enter coupon codes, and are redirected to the payment gateway.
     */
    public function index(Request $request)
    {
        $planSlug = $request->query('plan_slug');

        $plansQuery = SubscriptionPlan::active()->ordered();
        $plans = $plansQuery->get();

        $selectedPlan = null;
        if ($planSlug) {
            $selectedPlan = $plans->firstWhere('slug', $planSlug);
        }

        if (!$selectedPlan && $plans->isNotEmpty()) {
            $selectedPlan = $plans->first();
        }
        // Allow pre-calculated pricing data (e.g. after applying coupon) to be passed in
        $priceInfo = $request->get('priceInfo');
        $appliedCouponCode = $request->get('appliedCouponCode');

        return view('checkout.index', [
            'plans' => $plans,
            'selectedPlan' => $selectedPlan,
            'planSlug' => $planSlug,
            'source' => $request->query('source', 'web'),
            'priceInfo' => $priceInfo,
            'appliedCouponCode' => $appliedCouponCode,
            'episodeId' => $request->query('episode_id'),
        ]);
    }

    /**
     * Handle checkout form submission:
     *  - create pending subscription for the authenticated user
     *  - create pending payment
     *  - initiate Zarinpal payment and redirect to gateway URL
     */
    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'coupon_code' => 'nullable|string|max:64',
        ]);

        $action = $request->input('action', 'pay');
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->withErrors([
                'auth' => 'برای خرید اشتراک، ابتدا وارد حساب کاربری خود شوید.',
            ]);
        }

        $plan = SubscriptionPlan::active()->findOrFail($request->input('plan_id'));

        $source = $request->input('source', 'web');
        $returnScheme = $request->input('return_scheme');
        if (!$returnScheme && $source === 'app') {
            $returnScheme = 'sarvcast';
        }

        // Optional: episode context when checkout started from a specific episode in the app
        $episodeId = $request->input('episode_id');

        $couponCode = trim((string) $request->input('coupon_code', '')) ?: null;

        // First branch: user clicked "apply coupon" -> calculate and show price, do NOT start payment yet
        if ($action === 'apply_coupon') {
            try {
                $priceInfo = $this->calculatePriceForPlan($plan, $couponCode, $user);
            } catch (\RuntimeException $e) {
                return back()
                    ->withErrors(['coupon_code' => $e->getMessage()])
                    ->withInput();
            }

            $plans = SubscriptionPlan::active()->ordered()->get();

            // Re-render checkout with updated pricing and selected plan
            return view('checkout.index', [
                'plans' => $plans,
                'selectedPlan' => $plan,
                'planSlug' => $plan->slug,
                'source' => $source,
                'priceInfo' => $priceInfo,
                'appliedCouponCode' => $couponCode,
            ]);
        }

        // Otherwise: user clicked the main "pay" button -> calculate final price and start payment
        try {
            $priceInfo = $this->calculatePriceForPlan($plan, $couponCode, $user);
        } catch (\RuntimeException $e) {
            return back()
                ->withErrors(['coupon_code' => $e->getMessage()])
                ->withInput();
        }

        // Amount for subscription is kept in IRT (toman) for consistency with the app
        $subscriptionPriceToman = $priceInfo['original_amount'] ?? $priceInfo['final_price'];

        // If final price is zero (e.g. 100% coupon), activate subscription immediately
        if ((int) ($priceInfo['final_price'] ?? 0) === 0 || (int) ($priceInfo['amount'] ?? 0) === 0) {
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'type' => $this->mapPlanSlugToEnum($plan->slug),
                'status' => 'active',
                'start_date' => now(),
                'end_date' => now()->copy()->addDays($plan->duration_days),
                'price' => $subscriptionPriceToman,
                'currency' => $plan->currency,
                'auto_renew' => false,
                'payment_method' => 'zarinpal',
            ]);

            // If flow started from the app, redirect back via deep link instead of payment gateway
            if ($source === 'app' && $returnScheme) {
                $timestamp = now()->toIso8601String();

                $params = [
                    'success' => 'true',
                    'subscription_id' => $subscription->id,
                    'amount' => 0,
                    'transaction_id' => 'FREE-COUPON-' . $subscription->id,
                    'timestamp' => $timestamp,
                ];

                if ($episodeId) {
                    $params['episode_id'] = $episodeId;
                }

                $deepLink = $returnScheme . '://payment/success?' . http_build_query($params);

                return redirect()->away($deepLink);
            }

            // Web flow: show standard success page without going to gateway
            return redirect()->route('payment.success')
                ->with('success', 'اشتراک شما با موفقیت و بدون نیاز به پرداخت فعال شد.');
        }

        // Create a pending subscription for this plan (normal paid flow)
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'type' => $this->mapPlanSlugToEnum($plan->slug),
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->copy()->addDays($plan->duration_days),
            'price' => $subscriptionPriceToman,
            'currency' => $plan->currency,
            'auto_renew' => false,
            'payment_method' => 'zarinpal',
        ]);

        // Create a pending payment record
        // NOTE: amount for the gateway is always in IRR (rial), so we use the converted amount here.
        $payment = Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => $priceInfo['amount'],
            'currency' => $priceInfo['currency'], // typically IRR for Zarinpal
            'status' => 'pending',
            'payment_method' => 'zarinpal',
            'payment_gateway' => 'zarinpal',
            'transaction_id' => 'PAY_' . time() . '_' . rand(1000, 9999),
            'payment_metadata' => [
                'source' => $source,
                'return_scheme' => $returnScheme,
                'price_info' => $priceInfo,
                'episode_id' => $episodeId,
            ],
        ]);

        // Initiate payment with Zarinpal
        $description = 'پرداخت اشتراک ' . $plan->name;
        $result = $this->paymentService->initiateZarinPalPayment($payment, $description);

        if (!($result['success'] ?? false)) {
            $payment->update(['status' => 'failed']);

            return back()->withErrors([
                'payment' => $result['message'] ?? 'خطا در ایجاد درخواست پرداخت',
            ])->withInput();
        }

        return redirect()->away($result['payment_url']);
    }

    /**
     * Calculate price for a given plan and optional coupon code
     * and prepare both display (IRT) and gateway (IRR) amounts.
     *
     * This mirrors the logic in Api\SubscriptionController@calculatePrice
     * so that web checkout and app API behave consistently.
     */
    private function calculatePriceForPlan(SubscriptionPlan $plan, ?string $couponCode, $user): array
    {
        $basePrice = $plan->price;
        $discount = $plan->discount_percentage ?? 0;
        $discountedPrice = $basePrice - ($basePrice * $discount / 100);

        $priceInfo = [
            'type' => $plan->slug,
            'name' => $plan->name,
            'base_price' => $basePrice,
            'discount_percentage' => $discount,
            'discounted_price' => $discountedPrice,
            'savings' => $basePrice - $discountedPrice,
            'currency' => $plan->currency ?? 'IRT',
            'duration_days' => $plan->duration_days ?? 30,
            'description' => $plan->description,
            'plan_id' => $plan->id,
        ];

        // Apply coupon discount if provided
        if ($couponCode) {
            $baseAmount = $priceInfo['discounted_price'] ?? $priceInfo['base_price'];

            $couponValidation = app(\App\Services\CouponService::class)->validateCouponCode(
                $couponCode,
                $user,
                $baseAmount
            );

            if ($couponValidation['success']) {
                $couponInfo = $couponValidation['data'];
                $priceInfo['original_price'] = $baseAmount;
                $priceInfo['coupon_discount'] = $couponInfo['discount_amount'];
                $priceInfo['final_price'] = $couponInfo['final_amount'];
                $priceInfo['coupon_code'] = $couponCode;
                $priceInfo['coupon_info'] = $couponInfo['coupon'];
            } else {
                throw new \RuntimeException($couponValidation['message']);
            }
        } else {
            $priceInfo['final_price'] = $priceInfo['discounted_price'] ?? $priceInfo['base_price'];
        }

        // Convert to IRR for the payment gateway if needed
        if ($priceInfo['currency'] === 'IRT') {
            $priceInfo['original_amount'] = $priceInfo['final_price'];
            $priceInfo['original_currency'] = 'IRT';
            $priceInfo['amount'] = (int) ($priceInfo['final_price'] * 10); // toman -> rial
            $priceInfo['currency'] = 'IRR';
            $priceInfo['conversion_rate'] = 10;
            $priceInfo['conversion_note'] = 'مبلغ از تومان به ریال برای درگاه پرداخت تبدیل شد';
        } else {
            $priceInfo['amount'] = (int) $priceInfo['final_price'];
        }

        return $priceInfo;
    }

    /**
     * Map plan slug to correct ENUM value for subscriptions.type
     * (kept in sync with Api\SubscriptionController::mapPlanSlugToEnum).
     */
    private function mapPlanSlugToEnum(string $planSlug): string
    {
        $slugMapping = [
            '1month' => '1month',
            '3months' => '3months',
            '6months' => '6months',
            '1year' => '1year',
            '3month' => '3months',
            '6month' => '6months',
            'monthly' => '1month',
            'quarterly' => '3months',
            'semi-annual' => '6months',
            'annual' => '1year',
            'yearly' => '1year',
            '1' => '1month',
            '2' => '3months',
            '3' => '6months',
            '4' => '1year',
        ];

        $mappedSlug = $slugMapping[$planSlug] ?? $planSlug;

        if (!in_array($mappedSlug, ['1month', '3months', '6months', '1year'])) {
            \Log::warning('CheckoutController: Unknown plan slug mapping for subscription type', [
                'plan_slug' => $planSlug,
                'mapped_slug' => $mappedSlug,
            ]);
        }

        return $mappedSlug;
    }
}


