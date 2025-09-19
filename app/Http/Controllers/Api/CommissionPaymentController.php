<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CommissionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommissionPaymentController extends Controller
{
    protected $commissionPaymentService;

    public function __construct(CommissionPaymentService $commissionPaymentService)
    {
        $this->commissionPaymentService = $commissionPaymentService;
    }

    public function getMyPayments(Request $request): JsonResponse
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

        $filters = $request->only(['status', 'payment_type', 'date_from', 'date_to']);

        $result = $this->commissionPaymentService->getPartnerPayments($partner->id, $filters);

        return response()->json($result);
    }

    public function getPaymentHistory(): JsonResponse
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

        $result = $this->commissionPaymentService->getPaymentHistory($partner->id);

        return response()->json($result);
    }

    // Admin methods
    public function getPendingPayments(): JsonResponse
    {
        $result = $this->commissionPaymentService->getPendingPayments();

        return response()->json($result);
    }

    public function processPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:commission_payments,id',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $processorId = Auth::id();
        $paymentData = $request->only(['payment_reference', 'notes']);

        $result = $this->commissionPaymentService->processPayment(
            $validated['payment_id'],
            $processorId,
            $paymentData
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function markAsPaid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:commission_payments,id',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $result = $this->commissionPaymentService->markAsPaid(
            $validated['payment_id'],
            $validated['payment_reference'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function markAsFailed(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:commission_payments,id',
            'reason' => 'nullable|string',
        ]);

        $result = $this->commissionPaymentService->markAsFailed(
            $validated['payment_id'],
            $validated['reason'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function createManualPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partner_id' => 'required|exists:affiliate_partners,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'payment_method' => 'nullable|string|max:50',
            'payment_details' => 'nullable|array',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $result = $this->commissionPaymentService->createManualPayment($validated);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    public function bulkProcessPayments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'exists:commission_payments,id',
        ]);

        $processorId = Auth::id();

        $result = $this->commissionPaymentService->bulkProcessPayments(
            $validated['payment_ids'],
            $processorId
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function getAllPayments(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'payment_type', 'date_from', 'date_to']);

        $result = $this->commissionPaymentService->getPaymentHistory();

        return response()->json($result);
    }

    public function getPaymentStatistics(): JsonResponse
    {
        $result = $this->commissionPaymentService->getPaymentStatistics();

        return response()->json($result);
    }
}
