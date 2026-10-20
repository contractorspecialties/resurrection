<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySubscription;

class SubscriptionEntitlementService
{
    public function hasPaidCoverage(Company $company): bool
    {
        return CompanySubscription::query()
            ->where('company_id', $company->id)
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
