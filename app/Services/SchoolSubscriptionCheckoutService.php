<?php

namespace App\Services;

use App\Exceptions\SchoolPanelException;
use App\Models\Payment;
use App\Models\SchoolManagerProfile;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Support\SchoolCheckoutUrl;
use Illuminate\Support\Facades\DB;

class SchoolSubscriptionCheckoutService
{
    /** @var list<string> */
    public const PLAN_SLUGS = ['1month', '3months', '6months', '1year'];

    public function __construct(
        private readonly SchoolManagerService $schoolManagers,
        private readonly PaymentService $payments,
        private readonly CouponService $coupons,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function plans(): array
    {
        return SubscriptionPlan::query()
            ->where('is_active', true)
            ->whereIn('slug', self::PLAN_SLUGS)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (SubscriptionPlan $plan): array {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                    'description' => $plan->description,
                    'duration_days' => (int) $plan->duration_days,
                    'price' => (int) $plan->price,
                    'currency' => $plan->currency ?? 'IRT',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function start(SchoolManagerProfile $profile, User $payer, int $memberId, int $planId, ?string $couponCode): array
    {
        $member = $this->schoolManagers->activeMember($profile, $memberId);
        $plan = SubscriptionPlan::query()->find($planId);
        if (! $plan || ! $plan->is_active) {
            throw new SchoolPanelException('plan_unavailable', 'پلن اشتراک در دسترس نیست.', 422);
        }

        if (! in_array((string) $plan->slug, self::PLAN_SLUGS, true)) {
            throw new SchoolPanelException('plan_unavailable', 'این پلن از پنل مدرسه قابل خرید نیست.', 422);
        }

        if ($this->memberAlreadyPremium($member)) {
            throw new SchoolPanelException(
                'already_premium',
                'اشتراک این کاربر فعال است و خرید دوباره لازم نیست.',
                409,
            );
        }

        $amount = (float) $plan->final_price;
        $couponCode = $couponCode !== null ? trim($couponCode) : null;
        if ($couponCode === '') {
            $couponCode = null;
        }

        if ($couponCode !== null) {
            $couponValidation = $this->coupons->validateCouponCode($couponCode, $member, $amount, (string) $plan->slug);
            if (! ($couponValidation['success'] ?? false)) {
                throw new SchoolPanelException(
                    'coupon_invalid',
                    (string) ($couponValidation['message'] ?? 'کد کوپن معتبر نیست.'),
                    400,
                );
            }
            $amount = (float) $couponValidation['data']['final_amount'];
        }

        $amountIrr = $this->toIrr($amount, (string) ($plan->currency ?? 'IRT'));
        $returnUrl = SchoolCheckoutUrl::memberUrl((int) $member->id, 'ok');
        $failureUrl = SchoolCheckoutUrl::memberUrl((int) $member->id, 'failed');

        $created = DB::transaction(function () use ($profile, $payer, $member, $plan, $amountIrr, $returnUrl, $failureUrl) {
            $lockedMember = $this->schoolManagers->activeMember($profile, (int) $member->id);
            if ($this->memberAlreadyPremium($lockedMember)) {
                throw new SchoolPanelException(
                    'already_premium',
                    'اشتراک این کاربر فعال است و خرید دوباره لازم نیست.',
                    409,
                );
            }

            $reusable = $this->reusablePayment($lockedMember, $profile);
            if ($reusable) {
                return ['payment' => $reusable, 'reused' => true];
            }

            $subscription = Subscription::query()->create([
                'user_id' => $lockedMember->id,
                'type' => $plan->slug,
                'price' => $amountIrr,
                'currency' => 'IRR',
                'status' => 'pending',
                'start_date' => null,
                'end_date' => null,
                'auto_renew' => false,
                'subscription_metadata' => [
                    'source' => 'school_panel',
                    'payer_user_id' => $payer->id,
                    'beneficiary_user_id' => $lockedMember->id,
                    'school_manager_profile_id' => $profile->id,
                ],
            ]);

            $payment = Payment::query()->create([
                'user_id' => $lockedMember->id,
                'subscription_id' => $subscription->id,
                'amount' => $amountIrr,
                'currency' => 'IRR',
                'payment_method' => 'zarinpal',
                'status' => 'pending',
                'transaction_id' => 'SUB_'.time().'_'.random_int(1000, 9999),
                'payment_metadata' => [
                    'source' => 'school_panel',
                    'payer_user_id' => $payer->id,
                    'beneficiary_user_id' => $lockedMember->id,
                    'school_manager_profile_id' => $profile->id,
                    'return_url' => $returnUrl,
                    'failure_url' => $failureUrl,
                ],
            ]);

            return ['payment' => $payment, 'subscription' => $subscription, 'reused' => false];
        });

        /** @var Payment $payment */
        $payment = $created['payment'];
        if ($created['reused'] === true) {
            return $this->payload($payment, $payer, true);
        }

        $description = $plan->name ?: 'اشتراک مانجی';
        try {
            $initiated = $this->payments->initiateZarinPalPayment($payment, $description);
        } catch (\Throwable) {
            $this->discardPending($payment);
            throw new SchoolPanelException('payment_failed', 'خطا در ارتباط با درگاه پرداخت.', 500);
        }

        if (! ($initiated['success'] ?? false) || empty($initiated['payment_url'])) {
            $this->discardPending($payment);
            throw new SchoolPanelException(
                'payment_failed',
                (string) ($initiated['message'] ?? 'خطا در ارتباط با درگاه پرداخت.'),
                500,
            );
        }

        $payment->refresh();

        return $this->payload($payment, $payer, false, (string) $initiated['payment_url'], (string) ($initiated['authority'] ?? ''));
    }

    private function memberAlreadyPremium(User $member): bool
    {
        return Subscription::query()
            ->where('user_id', $member->id)
            ->grantingAccess()
            ->exists();
    }

    private function reusablePayment(User $member, SchoolManagerProfile $profile): ?Payment
    {
        $pending = Payment::query()
            ->where('user_id', $member->id)
            ->where('status', 'pending')
            ->where('payment_method', 'zarinpal')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        foreach ($pending as $payment) {
            $metadata = $payment->payment_metadata ?? [];
            if (($metadata['source'] ?? null) !== 'school_panel') {
                continue;
            }
            if ((int) ($metadata['school_manager_profile_id'] ?? 0) !== (int) $profile->id) {
                continue;
            }
            $authority = (string) $payment->transaction_id;
            if ($authority === '' || str_starts_with($authority, 'SUB_')) {
                continue;
            }
            $subscription = $payment->subscription;
            if (! $subscription || $subscription->status !== 'pending') {
                continue;
            }
            if ((int) $subscription->user_id !== (int) $member->id) {
                continue;
            }

            return $payment;
        }

        return null;
    }

    private function discardPending(Payment $payment): void
    {
        $subscriptionId = $payment->subscription_id;
        $payment->delete();
        if ($subscriptionId) {
            Subscription::query()->whereKey($subscriptionId)->where('status', 'pending')->delete();
        }
    }

    private function toIrr(float $amount, string $currency): int
    {
        if ($currency === 'IRT') {
            return (int) ($amount * 10);
        }

        return (int) $amount;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Payment $payment, User $payer, bool $reused, ?string $paymentUrl = null, ?string $authority = null): array
    {
        $payment->loadMissing('subscription');
        $subscription = $payment->subscription;
        $authority = $authority !== null && $authority !== '' ? $authority : (string) $payment->transaction_id;
        $paymentUrl = $paymentUrl ?: SchoolCheckoutUrl::paymentUrl($authority);

        return [
            'reused' => $reused,
            'payment_url' => $paymentUrl,
            'authority' => $authority,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'user_id' => (int) $subscription->user_id,
                'type' => $subscription->type,
                'status' => $subscription->status,
                'price' => (int) $subscription->price,
                'currency' => $subscription->currency,
                'auto_renew' => (bool) $subscription->auto_renew,
            ] : null,
            'payment' => [
                'id' => $payment->id,
                'user_id' => (int) $payment->user_id,
                'subscription_id' => $payment->subscription_id,
                'amount' => (int) $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status,
                'payer_user_id' => (int) $payer->id,
                'beneficiary_user_id' => (int) $payment->user_id,
            ],
        ];
    }
}
