<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySubscription;

class SubscriptionEntitlementService
{
    public function hasPaidCoverage(Company $company): bool
    {
        $key = (string) config('services.stripe.secret');

        if (str_starts_with($key, 'sk_live_')) {
            $liveMode = true;
        } elseif (str_starts_with($key, 'sk_test_')) {
            $liveMode = false;
        } else {
            // Unknown Stripe environment never waives platform fees.
            return false;
        }

        return CompanySubscription::query()
            ->where('company_id', $company->id)
            ->where('stripe_livemode', $liveMode)
            ->whereNotNull('stripe_subscription_id')
            ->whereIn('status', ['active', 'canceled', 'past_due'])
            ->whereNull('revoked_at')
            ->where('paid_through_at', '>', now())
            ->exists();
    }

    public function platformFeeCents(
        Company $company,
        int $normalFeeCents
    ): int {
        return $this->hasPaidCoverage($company)
            ? 0
            : $normalFeeCents;
    }
}
