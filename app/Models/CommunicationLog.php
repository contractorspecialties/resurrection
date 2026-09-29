<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationLog extends Model
{
    protected $fillable = [
        'company_id',
        'client_id',
        'estimate_id',
        'quick_bill_id',
        'channel',
        'purpose',
        'recipient',
        'subject',
        'status',
        'provider',
        'provider_message_id',
        'message',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
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
}
