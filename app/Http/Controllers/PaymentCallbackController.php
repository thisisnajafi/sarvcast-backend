<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle ZarinPal payment callback
     */
    public function zarinpalCallback(Request $request)
    {
        $result = $this->paymentService->processCallback($request->all());
        
        if ($result['success']) {
            return redirect()->route('payment.success', [
                'payment_id' => $result['payment']->id
            ])->with('success', $result['message']);
        } else {
            return redirect()->route('payment.failure')->with('error', $result['message']);
        }
    }

    /**
     * Show payment success page
     */
    public function success(Request $request)
    {
        $paymentId = $request->get('payment_id');
        
        if ($paymentId) {
            $payment = \App\Models\Payment::with(['user', 'subscription'])->find($paymentId);
            
            if ($payment) {
                return view('payment.success', compact('payment'));
            }
        }
        
        return view('payment.success');
    }

    /**
     * Show payment failure page
     */
    public function failure(Request $request)
    {
        return view('payment.failure');
    }
}
