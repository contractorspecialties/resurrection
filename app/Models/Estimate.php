<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estimate extends Model
{
    protected $fillable = [
        'client_id',
        'estimate_number',
        'portal_token',
        'status',
        'version',
        'scope_summary',
        'notes',
        'subtotal_cents',
        'tax_cents',
        'total_cents',
        'deposit_cents',
        'tax_rate',
        'sent_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:3',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
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

    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class)->orderBy('position');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(EstimateAcceptance::class)->latest('accepted_at');
    }

    public function revisionRequests(): HasMany
    {
        return $this->hasMany(EstimateRevisionRequest::class)->latest('requested_at');
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
        return max(0, $this->total_cents - $this->paidAmountCents());
    }

    public function depositPaidCents(): int
    {
        return (int) $this->payments()
            ->whereIn('status', ['paid', 'refunded'])
            ->where('purpose', 'deposit')
            ->get(['amount_cents', 'refunded_amount_cents'])
            ->sum(fn ($payment) => max(
                0,
                $payment->amount_cents - $payment->refunded_amount_cents
            ));
    }

    public function money(int $cents): string
    {
        return '$' . number_format($cents / 100, 2);
    }

    public function portalUrl(): ?string
    {
        return $this->portal_token
            ? route('portal.show', $this->portal_token)
            : null;
    }

    public function acceptanceSnapshot(): array
    {
        $this->loadMissing(['client', 'items', 'company']);

        return [
            'estimate_number' => $this->estimate_number,
            'version' => $this->version,
            'company' => [
                'id' => $this->company_id,
                'name' => $this->company->name,
                'trade' => $this->company->trade,
                'city' => $this->company->city,
                'state' => $this->company->state,
            ],
            'client' => [
                'id' => $this->client_id,
                'name' => $this->client->name,
                'company_name' => $this->client->company_name,
                'email' => $this->client->email,
                'phone' => $this->client->phone,
            ],
            'scope_summary' => $this->scope_summary,
            'notes' => $this->notes,
            'subtotal_cents' => $this->subtotal_cents,
            'tax_cents' => $this->tax_cents,
            'total_cents' => $this->total_cents,
            'deposit_cents' => $this->deposit_cents,
            'tax_rate' => (string) $this->tax_rate,
            'items' => $this->items->map(fn ($item) => [
                'description' => $item->description,
                'details' => $item->details,
                'quantity' => (string) $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'line_total_cents' => $item->line_total_cents,
                'is_taxable' => (bool) $item->is_taxable,
                'position' => $item->position,
            ])->values()->all(),
        ];
    }
}
