<?php

namespace App\Notifications;

use App\Models\Estimate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EstimateAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $estimateId
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $estimate = Estimate::query()
            ->with('client')
            ->where('company_id', $notifiable->company_id)
            ->findOrFail($this->estimateId);

        return (new MailMessage)
            ->subject(
                'Estimate accepted: ' . $estimate->estimate_number
            )
            ->greeting('Good news!')
            ->line(
                ($estimate->client?->name ?? 'Your customer')
                . ' has accepted estimate '
                . $estimate->estimate_number . '.'
            )
            ->line(
                'The acceptance has been recorded. '
                . 'Review the estimate for payment status '
                . 'and next steps.'
            )
            ->action(
                'View Accepted Estimate',
                route('estimates.show', $estimate)
            );
    }
}
