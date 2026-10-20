<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySubscription extends Model
{
    protected $fillable = [
        'company_id',
        'stripe_customer_id',
        'stripe_subscription_id',
        'pending_checkout_attempt_id',
        'pending_checkout_session_id',
        'pending_checkout_expires_at',
        'status',
        'price_cents',
        'currency',
        'paid_through_at',
        'cancel_at_period_end',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'pending_checkout_expires_at' => 'datetime',
            'paid_through_at' => 'datetime',
            'cancel_at_period_end' => 'boolean',
            'revoked_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
