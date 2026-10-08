<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\Payment;
use App\Models\QuickBill;

class PaymentStateService
{
    public function reconcile(Payment $payment): void
    {
        if ($payment->estimate_id) {
            $this->reconcileEstimate($payment);
        }

        if ($payment->quick_bill_id && $payment->purpose === 'quick_bill') {
            $this->reconcileQuickBill($payment);
        }
    }

    private function reconcileEstimate(Payment $payment): void
    {
        $estimate = Estimate::query()
            ->lockForUpdate()
            ->find($payment->estimate_id);

        if (! $estimate) {
            return;
        }

        $paidAmount = $estimate->paidAmountCents();
        $depositPaid = $estimate->depositPaidCents();

        if ($paidAmount >= $estimate->total_cents) {
            $estimate->update(['status' => 'paid']);

            return;
        }

        if (in_array($estimate->status, ['paid', 'balance_due'], true)) {
            $estimate->update(['status' => 'balance_due']);

            return;
        }

        if (
            $estimate->deposit_cents > 0
            && $depositPaid < $estimate->deposit_cents
            && in_array($estimate->status, ['deposit_due', 'active_job'], true)
        ) {
            $estimate->update(['status' => 'deposit_due']);

            return;
        }

        if (
            $estimate->status === 'deposit_due'
            && $depositPaid >= $estimate->deposit_cents
        ) {
            $estimate->update(['status' => 'active_job']);
        }
    }

    private function reconcileQuickBill(Payment $payment): void
    {
        $quickBill = QuickBill::query()
            ->lockForUpdate()
            ->find($payment->quick_bill_id);

        if (! $quickBill) {
            return;
        }

        if ($quickBill->paidAmountCents() >= $quickBill->amount_cents) {
            if ($quickBill->status !== 'paid') {
                $quickBill->update([
                    'status' => 'paid',
                    'paid_at' => $payment->paid_at ?? now(),
                ]);
            }

            return;
        }

        if ($quickBill->status === 'paid') {
            $quickBill->update([
                'status' => 'payment_due',
                'paid_at' => null,
            ]);
        }
    }
}
