<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'company_id',
        'client_id',
        'estimate_id',
        'quick_bill_id',
        'provider',
        'purpose',
        'status',
        'active_checkout_key',
        'amount_cents',
        'platform_fee_cents',
        'currency',
        'provider_checkout_session_id',
        'provider_payment_intent_id',
        'provider_charge_id',
        'paid_at',
        'failed_at',
        'refunded_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function quickBill(): BelongsTo
    {
        return $this->belongsTo(QuickBill::class);
    }

    public function money(): string
    {
        return '$' . number_format($this->amount_cents / 100, 2);
    }
}
