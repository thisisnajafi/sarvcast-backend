<?php

namespace App\Services;

use App\Models\CommissionPayment;
use App\Models\AffiliatePartner;
use App\Models\CouponUsage;
use App\Events\InfluencerCommissionEvent;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CommissionPaymentService
{
    public function getPendingPayments(): array
    {
        try {
            $payments = CommissionPayment::with(['affiliatePartner', 'couponUsage', 'processor'])
                ->pending()
                ->orderBy('created_at', 'asc')
                ->get();

            return [
                'success' => true,
                'message' => 'پرداخت‌های در انتظار با موفقیت دریافت شد',
                'data' => $payments->map->toApiResponse()
            ];
        } catch (\Exception $e) {
            Log::error("Error getting pending payments: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت پرداخت‌های در انتظار'
            ];
        }
    }

    public function processPayment(int $paymentId, int $processorId, array $paymentData = []): array
    {
        try {
            DB::beginTransaction();

            $payment = CommissionPayment::findOrFail($paymentId);

            if (!$payment->isPending()) {
                throw new \Exception('این پرداخت قبلاً پردازش شده است');
            }

            $payment->update([
                'status' => 'processing',
                'processed_at' => now(),
                'processed_by' => $processorId,
                'payment_reference' => $paymentData['payment_reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'پرداخت با موفقیت پردازش شد',
                'data' => $payment->fresh()->toApiResponse()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error processing payment: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در پردازش پرداخت: ' . $e->getMessage()
            ];
        }
    }

    public function markAsPaid(int $paymentId, string $paymentReference = null): array
    {
        try {
            DB::beginTransaction();

            $payment = CommissionPayment::findOrFail($paymentId);

            if (!$payment->isProcessing()) {
                throw new \Exception('این پرداخت در حال پردازش نیست');
            }

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_reference' => $paymentReference,
            ]);

            // Fire influencer commission notification event
            event(new InfluencerCommissionEvent($payment));

            DB::commit();

            return [
                'success' => true,
                'message' => 'پرداخت با موفقیت تکمیل شد',
                'data' => $payment->fresh()->toApiResponse()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error marking payment as paid: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در تکمیل پرداخت: ' . $e->getMessage()
            ];
        }
    }

    public function markAsFailed(int $paymentId, string $reason = null): array
    {
        try {
            DB::beginTransaction();

            $payment = CommissionPayment::findOrFail($paymentId);

            $payment->update([
                'status' => 'failed',
                'notes' => $reason ? ($payment->notes . "\n" . $reason) : $payment->notes,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'پرداخت به عنوان ناموفق علامت‌گذاری شد',
                'data' => $payment->fresh()->toApiResponse()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error marking payment as failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در علامت‌گذاری پرداخت: ' . $e->getMessage()
            ];
        }
    }

    public function getPartnerPayments(int $partnerId, array $filters = []): array
    {
        try {
            $query = CommissionPayment::with(['couponUsage', 'processor'])
                ->forPartner($partnerId);

            // Apply filters
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (!empty($filters['payment_type'])) {
                $query->where('payment_type', $filters['payment_type']);
            }

            if (!empty($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            $payments = $query->orderBy('created_at', 'desc')->get();

            return [
                'success' => true,
                'message' => 'پرداخت‌های شریک با موفقیت دریافت شد',
                'data' => $payments->map->toApiResponse()
            ];
        } catch (\Exception $e) {
            Log::error("Error getting partner payments: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت پرداخت‌های شریک'
            ];
        }
    }

    public function getPaymentStatistics(): array
    {
        try {
            $stats = [
                'total_payments' => CommissionPayment::count(),
                'pending_payments' => CommissionPayment::pending()->count(),
                'processing_payments' => CommissionPayment::processing()->count(),
                'paid_payments' => CommissionPayment::paid()->count(),
                'failed_payments' => CommissionPayment::where('status', 'failed')->count(),
                'total_amount_pending' => CommissionPayment::pending()->sum('amount'),
                'total_amount_paid' => CommissionPayment::paid()->sum('amount'),
                'average_payment_amount' => CommissionPayment::avg('amount'),
                'payments_by_type' => CommissionPayment::select('payment_type', DB::raw('COUNT(*) as count'))
                    ->groupBy('payment_type')
                    ->get()
                    ->pluck('count', 'payment_type'),
                'recent_payments' => CommissionPayment::recent(30)->count(),
            ];

            return [
                'success' => true,
                'message' => 'آمار پرداخت‌ها با موفقیت دریافت شد',
                'data' => $stats
            ];
        } catch (\Exception $e) {
            Log::error("Error getting payment statistics: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت آمار پرداخت‌ها'
            ];
        }
    }

    public function createManualPayment(array $data): array
    {
        try {
            DB::beginTransaction();

            $partner = AffiliatePartner::findOrFail($data['partner_id']);

            $payment = CommissionPayment::create([
                'affiliate_partner_id' => $partner->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'IRR',
                'payment_type' => 'manual',
                'status' => 'pending',
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'payment_details' => $data['payment_details'] ?? [],
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'پرداخت دستی با موفقیت ایجاد شد',
                'data' => $payment->toApiResponse()
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating manual payment: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در ایجاد پرداخت دستی: ' . $e->getMessage()
            ];
        }
    }

    public function bulkProcessPayments(array $paymentIds, int $processorId): array
    {
        try {
            DB::beginTransaction();

            $payments = CommissionPayment::whereIn('id', $paymentIds)
                ->where('status', 'pending')
                ->get();

            if ($payments->isEmpty()) {
                throw new \Exception('هیچ پرداخت در انتظاری یافت نشد');
            }

            $processedCount = 0;
            foreach ($payments as $payment) {
                $payment->update([
                    'status' => 'processing',
                    'processed_at' => now(),
                    'processed_by' => $processorId,
                ]);
                $processedCount++;
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "{$processedCount} پرداخت با موفقیت پردازش شد",
                'data' => ['processed_count' => $processedCount]
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error bulk processing payments: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در پردازش دسته‌ای پرداخت‌ها: ' . $e->getMessage()
            ];
        }
    }

    public function getPaymentHistory(int $partnerId = null): array
    {
        try {
            $query = CommissionPayment::with(['affiliatePartner', 'couponUsage', 'processor']);

            if ($partnerId) {
                $query->forPartner($partnerId);
            }

            $payments = $query->orderBy('created_at', 'desc')->get();

            return [
                'success' => true,
                'message' => 'تاریخچه پرداخت‌ها با موفقیت دریافت شد',
                'data' => $payments->map->toApiResponse()
            ];
        } catch (\Exception $e) {
            Log::error("Error getting payment history: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'خطا در دریافت تاریخچه پرداخت‌ها'
            ];
        }
    }

    /**
     * Send payment notification to affiliate
     */
    public function sendPaymentNotification(int $paymentId): array
    {
        try {
            $payment = CommissionPayment::with('affiliatePartner')->findOrFail($paymentId);
            
            if (!$payment->isCompleted()) {
                return [
                    'success' => false,
                    'message' => 'فقط برای پرداخت‌های تکمیل شده می‌توان اطلاع‌رسانی ارسال کرد'
                ];
            }

            $smsService = new SmsService();
            $result = $smsService->sendPaymentNotification(
                $payment->affiliatePartner->phone_number,
                $payment->amount,
                $payment->currency
            );

            if ($result['success']) {
                // Update payment with notification sent
                $payment->update([
                    'notification_sent_at' => now(),
                    'notification_status' => 'sent'
                ]);

                Log::info('Payment notification sent', [
                    'payment_id' => $paymentId,
                    'affiliate_id' => $payment->affiliate_partner_id,
                    'amount' => $payment->amount
                ]);

                return [
                    'success' => true,
                    'message' => 'اطلاع‌رسانی پرداخت با موفقیت ارسال شد'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'خطا در ارسال اطلاع‌رسانی: ' . ($result['error'] ?? 'خطای نامشخص')
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error sending payment notification', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال اطلاع‌رسانی پرداخت'
            ];
        }
    }

    /**
     * Send bulk payment notifications
     */
    public function sendBulkPaymentNotifications(array $paymentIds): array
    {
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($paymentIds as $paymentId) {
            $result = $this->sendPaymentNotification($paymentId);
            $results[] = [
                'payment_id' => $paymentId,
                'result' => $result
            ];

            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        return [
            'success' => $errorCount === 0,
            'message' => "اطلاع‌رسانی‌ها ارسال شد: {$successCount} موفق، {$errorCount} ناموفق",
            'data' => [
                'total' => count($paymentIds),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'results' => $results
            ]
        ];
    }
}
