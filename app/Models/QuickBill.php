<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuickBill extends Model
{
    protected $fillable = [
        'client_id',
        'quick_bill_number',
        'portal_token',
        'status',
        'description',
        'details',
        'amount_cents',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paidAmountCents(): int
    {
        return (int) $this->payments()
            ->whereIn('status', ['paid', 'refunded'])
            ->get(['amount_cents', 'refunded_amount_cents'])
            ->sum(fn ($payment) => max(
                0,
                $payment->amount_cents - $payment->refunded_amount_cents
            ));
    }

    public function balanceDueCents(): int
    {
        return max(0, $this->amount_cents - $this->paidAmountCents());
    }

    public function money(int $cents): string
    {
        return '$' . number_format($cents / 100, 2);
    }

    public function portalUrl(): string
    {
        return route('portal.quick-bill.show', $this->portal_token);
    }
}
