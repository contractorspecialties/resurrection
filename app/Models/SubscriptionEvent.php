<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionEvent extends Model
{
    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'stripe_customer_id',
        'stripe_subscription_id',
        'payload',
        'processed_at',
        'processing_error',
    ];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
