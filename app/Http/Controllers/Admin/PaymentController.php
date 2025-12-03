<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'subscription']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('payment_gateway')) {
            $query->where('payment_gateway', $request->payment_gateway);
        }

        if ($request->filled('billing_platform')) {
            $query->where('billing_platform', $request->billing_platform);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('min_amount')) {
            $query->where('amount', '>=', $request->min_amount);
        }

        if ($request->filled('max_amount')) {
            $query->where('amount', '<=', $request->max_amount);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('transaction_id', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function($userQuery) use ($request) {
                      $userQuery->where('first_name', 'like', '%' . $request->search . '%')
                               ->orWhere('last_name', 'like', '%' . $request->search . '%')
                               ->orWhere('phone_number', 'like', '%' . $request->search . '%');
                  });
            });
        }

        // Apply sorting
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        
        switch ($sort) {
            case 'amount':
                $query->orderBy('amount', $direction);
                break;
            case 'status':
                $query->orderBy('status', $direction);
                break;
            case 'payment_method':
                $query->orderBy('payment_method', $direction);
                break;
            case 'processed_at':
                $query->orderBy('processed_at', $direction);
                break;
            default:
                $query->orderBy('created_at', $direction);
        }

        $perPage = $request->get('per_page', 20);
        $perPage = min($perPage, 100); // Max 100 per page

        $payments = $query->paginate($perPage);
        
        // Get filter options
        $users = User::select('id', 'first_name', 'last_name', 'phone_number')->get();
        
        // Get statistics
        $stats = [
            'total' => Payment::count(),
            'completed' => Payment::where('status', 'completed')->count(),
            'pending' => Payment::where('status', 'pending')->count(),
            'failed' => Payment::where('status', 'failed')->count(),
            'refunded' => Payment::where('status', 'refunded')->count(),
            'total_amount' => Payment::where('status', 'completed')->sum('amount'),
            'total_net_amount' => Payment::where('status', 'completed')->sum('net_amount'),
            'avg_amount' => Payment::where('status', 'completed')->avg('amount'),
            'success_rate' => Payment::count() > 0 ? round((Payment::where('status', 'completed')->count() / Payment::count()) * 100, 2) : 0
        ];

        return view('admin.payments.index', compact('payments', 'users', 'stats'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        $payment->load(['user', 'subscription']);
        
        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        $payment->load(['user', 'subscription']);
        
        return view('admin.payments.edit', compact('payment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded,cancelled',
            'refund_reason' => 'nullable|string|max:500',
            'refund_amount' => 'nullable|numeric|min:0|max:' . $payment->amount,
            'gateway_fee' => 'nullable|numeric|min:0',
            'net_amount' => 'nullable|numeric|min:0',
            'payment_metadata' => 'nullable|array'
        ], [
            'status.required' => 'وضعیت پرداخت الزامی است',
            'status.in' => 'وضعیت پرداخت نامعتبر است',
            'refund_reason.max' => 'دلیل بازگشت نمی‌تواند بیشتر از 500 کاراکتر باشد',
            'refund_amount.max' => 'مبلغ بازگشت نمی‌تواند بیشتر از مبلغ اصلی باشد',
            'gateway_fee.min' => 'کارمزد درگاه نمی‌تواند منفی باشد',
            'net_amount.min' => 'مبلغ خالص نمی‌تواند منفی باشد'
        ]);

        try {
            DB::beginTransaction();

            $data = $request->only(['status', 'refund_reason', 'refund_amount', 'gateway_fee', 'net_amount', 'payment_metadata']);

            // Handle refund
            if ($request->status === 'refunded' && $request->filled('refund_amount')) {
                $data['refunded_at'] = now();
                $data['refund_amount'] = $request->refund_amount;
            }

            // Handle completion
            if ($request->status === 'completed' && $payment->status !== 'completed') {
                $data['processed_at'] = now();
            }

            $payment->update($data);

            // Update subscription status if payment is completed
            if ($payment->status === 'completed' && $payment->subscription) {
                $payment->subscription->update(['status' => 'active']);
            }

            DB::commit();

            return redirect()->route('admin.payments.index')
                ->with('success', 'پرداخت با موفقیت به‌روزرسانی شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'خطا در به‌روزرسانی پرداخت: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        try {
            // Only allow deletion of failed or cancelled payments
            if (!in_array($payment->status, ['failed', 'cancelled'])) {
                return redirect()->route('admin.payments.index')
                    ->with('error', 'فقط پرداخت‌های ناموفق یا لغو شده قابل حذف هستند.');
            }

            $payment->delete();

            return redirect()->route('admin.payments.index')
                ->with('success', 'پرداخت با موفقیت حذف شد.');

        } catch (\Exception $e) {
            Log::error('Failed to delete payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.payments.index')
                ->with('error', 'خطا در حذف پرداخت: ' . $e->getMessage());
        }
    }

    /**
     * Bulk operations on payments
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:mark_completed,mark_failed,mark_refunded,delete',
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'integer|exists:payments,id',
            'refund_reason' => 'required_if:action,mark_refunded|string|max:500',
            'refund_amount' => 'required_if:action,mark_refunded|numeric|min:0'
        ], [
            'action.required' => 'عملیات الزامی است',
            'action.in' => 'عملیات نامعتبر است',
            'payment_ids.required' => 'انتخاب حداقل یک پرداخت الزامی است',
            'payment_ids.array' => 'شناسه‌های پرداخت باید آرایه باشند',
            'payment_ids.min' => 'حداقل یک پرداخت باید انتخاب شود',
            'payment_ids.*.exists' => 'یکی از پرداخت‌ها یافت نشد',
            'refund_reason.required_if' => 'دلیل بازگشت الزامی است',
            'refund_amount.required_if' => 'مبلغ بازگشت الزامی است'
        ]);

        try {
            DB::beginTransaction();

            $paymentIds = $request->payment_ids;
            $action = $request->action;
            $successCount = 0;
            $failureCount = 0;

            foreach ($paymentIds as $paymentId) {
                try {
                    $payment = Payment::findOrFail($paymentId);

                    switch ($action) {
                        case 'mark_completed':
                            if ($payment->status === 'pending') {
                                $payment->update([
                                    'status' => 'completed',
                                    'processed_at' => now()
                                ]);
                                
                                // Update subscription status
                                if ($payment->subscription) {
                                    $payment->subscription->update(['status' => 'active']);
                                }
                            }
                            break;

                        case 'mark_failed':
                            if (in_array($payment->status, ['pending', 'completed'])) {
                                $payment->update(['status' => 'failed']);
                            }
                            break;

                        case 'mark_refunded':
                            if ($payment->status === 'completed') {
                                $payment->update([
                                    'status' => 'refunded',
                                    'refunded_at' => now(),
                                    'refund_reason' => $request->refund_reason,
                                    'refund_amount' => $request->refund_amount
                                ]);
                                
                                // Update subscription status
                                if ($payment->subscription) {
                                    $payment->subscription->update(['status' => 'cancelled']);
                                }
                            }
                            break;

                        case 'delete':
                            if (in_array($payment->status, ['failed', 'cancelled'])) {
                                $payment->delete();
                            }
                            break;
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('Bulk action failed for payment', [
                        'payment_id' => $paymentId,
                        'action' => $action,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            $message = "عملیات {$action} روی {$successCount} پرداخت انجام شد";
            if ($failureCount > 0) {
                $message .= " و {$failureCount} پرداخت ناموفق بود";
            }

            return redirect()->route('admin.payments.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk action failed', [
                'action' => $request->action,
                'payment_ids' => $request->payment_ids,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('admin.payments.index')
                ->with('error', 'خطا در انجام عملیات گروهی: ' . $e->getMessage());
        }
    }

    /**
     * Export payments
     */
    public function export(Request $request)
    {
        $query = Payment::with(['user', 'subscription']);

        // Apply same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->get();

        $csvData = [];
        $csvData[] = [
            'ID', 'کاربر', 'مبلغ', 'واحد پول', 'وضعیت', 'روش پرداخت', 'درگاه پرداخت',
            'شناسه تراکنش', 'کارمزد درگاه', 'مبلغ خالص', 'تاریخ ایجاد', 'تاریخ پردازش'
        ];

        foreach ($payments as $payment) {
            $csvData[] = [
                $payment->id,
                $payment->user ? $payment->user->first_name . ' ' . $payment->user->last_name : '',
                $payment->amount,
                $payment->currency,
                $payment->status,
                $payment->payment_method,
                $payment->payment_gateway,
                $payment->transaction_id,
                $payment->gateway_fee,
                $payment->net_amount,
                $payment->created_at->format('Y-m-d H:i:s'),
                $payment->processed_at ? $payment->processed_at->format('Y-m-d H:i:s') : ''
            ];
        }

        $filename = 'payments_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get payment statistics
     */
    public function statistics()
    {
        $stats = [
            'total_payments' => Payment::count(),
            'completed_payments' => Payment::where('status', 'completed')->count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'failed_payments' => Payment::where('status', 'failed')->count(),
            'refunded_payments' => Payment::where('status', 'refunded')->count(),
            'total_amount' => Payment::where('status', 'completed')->sum('amount'),
            'total_net_amount' => Payment::where('status', 'completed')->sum('net_amount'),
            'avg_amount' => Payment::where('status', 'completed')->avg('amount'),
            'success_rate' => Payment::count() > 0 ? round((Payment::where('status', 'completed')->count() / Payment::count()) * 100, 2) : 0,
            'payments_by_status' => Payment::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get(),
            'payments_by_method' => Payment::selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total_amount')
                ->groupBy('payment_method')
                ->get(),
            'recent_payments' => Payment::with(['user', 'subscription'])
                ->latest()
                ->limit(10)
                ->get(),
            'daily_revenue' => Payment::where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum('amount'),
            'monthly_revenue' => Payment::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Process refund
     */
    public function processRefund(Request $request, Payment $payment)
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:0|max:' . $payment->amount,
            'refund_reason' => 'required|string|max:500'
        ], [
            'refund_amount.required' => 'مبلغ بازگشت الزامی است',
            'refund_amount.max' => 'مبلغ بازگشت نمی‌تواند بیشتر از مبلغ اصلی باشد',
            'refund_reason.required' => 'دلیل بازگشت الزامی است',
            'refund_reason.max' => 'دلیل بازگشت نمی‌تواند بیشتر از 500 کاراکتر باشد'
        ]);

        try {
            DB::beginTransaction();

            if ($payment->status !== 'completed') {
                return redirect()->back()
                    ->with('error', 'فقط پرداخت‌های تکمیل شده قابل بازگشت هستند.');
            }

            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
                'refund_reason' => $request->refund_reason,
                'refund_amount' => $request->refund_amount
            ]);

            // Update subscription status
            if ($payment->subscription) {
                $payment->subscription->update(['status' => 'cancelled']);
            }

            DB::commit();

            return redirect()->route('admin.payments.show', $payment)
                ->with('success', 'بازگشت پرداخت با موفقیت انجام شد.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process refund', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'خطا در بازگشت پرداخت: ' . $e->getMessage());
        }
    }
}
