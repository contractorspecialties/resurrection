<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\QuickBill;
use App\Services\PaymentStateService;
use App\Services\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ManualPaymentController extends Controller
{
    public function estimate(
        Request $request,
        Estimate $estimate,
        PaymentStateService $paymentState,
        StripeConnectService $stripe
    ): RedirectResponse {
        abort_unless(
            $estimate->company_id === $request->user()->company_id,
            404
        );

        $data = $request->validate([
            'method' => ['required', Rule::in(['cash', 'check'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $amountCents = (int) round(((float) $data['amount']) * 100);

        DB::transaction(function () use (
            $estimate,
            $amountCents,
            $data,
            $paymentState,
            $stripe
        ) {
            $estimate = Estimate::query()
                ->with('company')
                ->lockForUpdate()
                ->findOrFail($estimate->id);

            abort_unless(
                in_array(
                    $estimate->status,
                    ['deposit_due', 'active_job', 'balance_due'],
                    true
                ),
                409,
                'This estimate is not currently waiting for a payment.'
            );

            $purpose = $estimate->status === 'deposit_due'
                ? 'deposit'
                : 'final';

            $amountDue = $purpose === 'deposit'
                ? max(
                    0,
                    $estimate->deposit_cents - $estimate->depositPaidCents()
                )
                : $estimate->balanceDueCents();

            abort_if(
                $amountDue <= 0,
                409,
                'Nothing is currently due.'
            );

            abort_if(
                $amountCents > $amountDue,
                422,
                'Manual payment cannot exceed the amount due.'
            );

            $stripe->retireActiveCheckout(
                $estimate->company,
                "estimate:{$estimate->id}:{$purpose}",
                'superseded_by_manual_payment'
            );

            $payment = $estimate->payments()->create([
                'company_id' => $estimate->company_id,
                'client_id' => $estimate->client_id,
                'provider' => 'manual',
                'purpose' => $purpose,
                'status' => 'paid',
                'amount_cents' => $amountCents,
                'platform_fee_cents' => 0,
                'currency' => 'usd',
                'paid_at' => now(),
                'metadata' => [
                    'manual_method' => $data['method'],
                    'note' => $data['note'] ?? null,
                    'recorded_by_user_id' => request()->user()->id,
                ],
            ]);

            $paymentState->reconcile($payment);
        });

        return back()->with(
            'status',
            ucfirst($data['method']).' payment recorded.'
        );
    }

    public function quickBill(
        Request $request,
        QuickBill $quickBill,
        PaymentStateService $paymentState,
        StripeConnectService $stripe
    ): RedirectResponse {
        abort_unless(
            $quickBill->company_id === $request->user()->company_id,
            404
        );

        $data = $request->validate([
            'method' => ['required', Rule::in(['cash', 'check'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $amountCents = (int) round(((float) $data['amount']) * 100);

        DB::transaction(function () use (
            $quickBill,
            $amountCents,
            $data,
            $paymentState,
            $stripe
        ) {
            $quickBill = QuickBill::query()
                ->with('company')
                ->lockForUpdate()
                ->findOrFail($quickBill->id);

            abort_unless(
                $quickBill->status === 'payment_due',
                409,
                'This Quick Bill is not due.'
            );

            $amountDue = $quickBill->balanceDueCents();

            abort_if(
                $amountDue <= 0,
                409,
                'Nothing is currently due.'
            );

            abort_if(
                $amountCents > $amountDue,
                422,
                'Manual payment cannot exceed the amount due.'
            );

            $stripe->retireActiveCheckout(
                $quickBill->company,
                "quick_bill:{$quickBill->id}",
                'superseded_by_manual_payment'
            );

            $payment = $quickBill->payments()->create([
                'company_id' => $quickBill->company_id,
                'client_id' => $quickBill->client_id,
                'provider' => 'manual',
                'purpose' => 'quick_bill',
                'status' => 'paid',
                'amount_cents' => $amountCents,
                'platform_fee_cents' => 0,
                'currency' => 'usd',
                'paid_at' => now(),
                'metadata' => [
                    'manual_method' => $data['method'],
                    'note' => $data['note'] ?? null,
                    'recorded_by_user_id' => request()->user()->id,
                ],
            ]);

            $paymentState->reconcile($payment);
        });

        return back()->with(
            'status',
            ucfirst($data['method']).' payment recorded.'
        );
    }
}
