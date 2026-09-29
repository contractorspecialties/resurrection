<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceBookItem extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'category',
        'description',
        'unit_price_cents',
        'is_taxable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function money(): string
    {
        return '$'.number_format($this->unit_price_cents / 100, 2);
    }
}
