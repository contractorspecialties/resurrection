<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'trade',
        'city',
        'state',
        'preferred_customer_contact',
        'stripe_account_id',
        'stripe_details_submitted',
        'stripe_charges_enabled',
        'stripe_payouts_enabled',
    ];

    protected function casts(): array
    {
        return [
            'stripe_details_submitted' => 'boolean',
            'stripe_charges_enabled' => 'boolean',
            'stripe_payouts_enabled' => 'boolean',
        ];
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * Active customers only.
     *
     * Historical estimate/payment relations point directly at Client and therefore
     * continue to resolve archived customers correctly.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class)->whereNull('archived_at');
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    public function quickBills(): HasMany
    {
        return $this->hasMany(QuickBill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function hasHealthyStripeConnection(): bool
    {
        return filled($this->stripe_account_id)
            && $this->stripe_details_submitted
            && $this->stripe_charges_enabled
            && $this->stripe_payouts_enabled;
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'contractor';
        $slug = $base;
        $counter = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
