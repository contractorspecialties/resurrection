<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\QuickBill;
use App\Services\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalPaymentController extends Controller
{
    public function deposit(string $token, StripeConnectService $stripe): RedirectResponse
    {
        $estimate = Estimate::query()
            ->with(['company', 'client'])
            ->where('portal_token', $token)
            ->firstOrFail();

        $payment = $stripe->createEstimateCheckout($estimate, 'deposit');

        return redirect()->away($payment->getAttribute('checkout_url'));
    }

    public function finalBalance(string $token, StripeConnectService $stripe): RedirectResponse
    {
        $estimate = Estimate::query()
            ->with(['company', 'client'])
            ->where('portal_token', $token)
            ->firstOrFail();

        $payment = $stripe->createEstimateCheckout($estimate, 'final');

        return redirect()->away($payment->getAttribute('checkout_url'));
    }

    public function quickBill(string $token, StripeConnectService $stripe): RedirectResponse
    {
        $quickBill = QuickBill::query()
            ->with(['company', 'client'])
            ->where('portal_token', $token)
            ->firstOrFail();

        $payment = $stripe->createQuickBillCheckout($quickBill);

        return redirect()->away($payment->getAttribute('checkout_url'));
    }

    public function returned(Request $request, string $token): View
    {
        $estimate = Estimate::query()
            ->with(['company', 'client'])
            ->where('portal_token', $token)
            ->firstOrFail();

        $sessionId = $request->string('session_id')->toString();

        $payment = $estimate->payments()
            ->where('provider', 'stripe')
            ->where('provider_checkout_session_id', $sessionId)
            ->latest()
            ->first();

        return view('portal.payment-return', compact('estimate', 'payment'));
    }

    public function quickBillReturned(Request $request, string $token): View
    {
        $quickBill = QuickBill::query()
            ->with(['company', 'client'])
            ->where('portal_token', $token)
            ->firstOrFail();

        $sessionId = $request->string('session_id')->toString();

        $payment = $quickBill->payments()
            ->where('provider', 'stripe')
            ->where('provider_checkout_session_id', $sessionId)
            ->latest()
            ->first();

        return view('portal.quick-bill-return', compact('quickBill', 'payment'));
    }
}
