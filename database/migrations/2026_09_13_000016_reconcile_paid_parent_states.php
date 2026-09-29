<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Repair any historical parent records whose payment was already marked
         * paid before the v0.7 parent-state transitions were deployed.
         */

        $estimateIds = DB::table('payments')
            ->where('status', 'paid')
            ->whereNotNull('estimate_id')
            ->pluck('estimate_id')
            ->unique();

        foreach ($estimateIds as $estimateId) {
            $estimate = DB::table('estimates')->where('id', $estimateId)->first();

            if (! $estimate) {
                continue;
            }

            $paidTotal = (int) DB::table('payments')
                ->where('estimate_id', $estimateId)
                ->where('status', 'paid')
                ->sum('amount_cents');

            $depositPaid = (int) DB::table('payments')
                ->where('estimate_id', $estimateId)
                ->where('status', 'paid')
                ->where('purpose', 'deposit')
                ->sum('amount_cents');

            if ($paidTotal >= (int) $estimate->total_cents) {
                DB::table('estimates')
                    ->where('id', $estimateId)
                    ->update([
                        'status' => 'paid',
                        'updated_at' => now(),
                    ]);

                continue;
            }

            if (
                $depositPaid >= (int) $estimate->deposit_cents
                && $estimate->status === 'deposit_due'
            ) {
                DB::table('estimates')
                    ->where('id', $estimateId)
                    ->update([
                        'status' => 'active_job',
                        'updated_at' => now(),
                    ]);
            }
        }

        $quickBillIds = DB::table('payments')
            ->where('status', 'paid')
            ->whereNotNull('quick_bill_id')
            ->where('purpose', 'quick_bill')
            ->pluck('quick_bill_id')
            ->unique();

        foreach ($quickBillIds as $quickBillId) {
            $quickBill = DB::table('quick_bills')->where('id', $quickBillId)->first();

            if (! $quickBill) {
                continue;
            }

            $paidTotal = (int) DB::table('payments')
                ->where('quick_bill_id', $quickBillId)
                ->where('status', 'paid')
                ->sum('amount_cents');

            if ($paidTotal >= (int) $quickBill->amount_cents) {
                $paidAt = DB::table('payments')
                    ->where('quick_bill_id', $quickBillId)
                    ->where('status', 'paid')
                    ->max('paid_at');

                DB::table('quick_bills')
                    ->where('id', $quickBillId)
                    ->update([
                        'status' => 'paid',
                        'paid_at' => $paidAt ?: now(),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Reconciliation is intentionally irreversible.
        // We do not rewrite paid records back into unpaid states.
    }
};
