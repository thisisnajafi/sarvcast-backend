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

        return view('checkout.index', [
            'plans' => $plans,
            'selectedPlan' => $selectedPlan,
            'planSlug' => $planSlug,
            'source' => $request->query('source', 'web'),
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
        ]);

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

        // Create a pending subscription for this plan
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'type' => $plan->slug,
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->copy()->addDays($plan->duration_days),
            'price' => $plan->final_price,
            'currency' => $plan->currency,
            'auto_renew' => false,
            'payment_method' => 'zarinpal',
        ]);

        // Create a pending payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => $subscription->price,
            'currency' => $subscription->currency,
            'status' => 'pending',
            'payment_method' => 'zarinpal',
            'payment_gateway' => 'zarinpal',
            'transaction_id' => 'PAY_' . time() . '_' . rand(1000, 9999),
            'payment_metadata' => [
                'source' => $source,
                'return_scheme' => $returnScheme,
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
}


