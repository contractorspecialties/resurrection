<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\Payment;
use App\Models\QuickBill;

class PaymentStateService
{
    public function reconcile(Payment $payment): void
    {
        if ($payment->status !== 'paid') {
            return;
        }

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

        if (
            $payment->purpose === 'deposit'
            && $estimate->depositPaidCents() >= $estimate->deposit_cents
            && $estimate->status === 'deposit_due'
        ) {
            $estimate->update(['status' => 'active_job']);
        }

        if (
            $payment->purpose === 'final'
            && $estimate->paidAmountCents() >= $estimate->total_cents
        ) {
            $estimate->update(['status' => 'paid']);
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
            $quickBill->update([
                'status' => 'paid',
                'paid_at' => $payment->paid_at ?? now(),
            ]);
        }
    }
}
