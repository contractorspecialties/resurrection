<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateAcceptance extends Model
{
    protected $fillable = [
        'estimate_id',
        'company_id',
        'client_id',
        'estimate_version',
        'signature_name',
        'accepted_at',
        'snapshot',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }
}
