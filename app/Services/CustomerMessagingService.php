<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CommunicationLog;
use App\Models\Company;
use App\Models\Estimate;
use App\Models\QuickBill;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CustomerMessagingService
{
    public function sendEstimateEmail(Estimate $estimate): CommunicationLog
    {
        $estimate->loadMissing(['company', 'client']);

        $recipient = trim((string) $estimate->client->email);

        if ($recipient === '') {
            throw new RuntimeException('This customer does not have an email address.');
        }

        $subject = "{$estimate->company->name}: {$estimate->estimate_number}";
        $message = "Your estimate is ready. Review the scope, price and terms here: {$estimate->portalUrl()}";

        $log = $this->makeLog(
            company: $estimate->company,
            client: $estimate->client,
            estimate: $estimate,
            channel: 'email',
            recipient: $recipient,
            purpose: 'estimate',
            subject: $subject,
            message: $message,
        );

        try {
            Mail::html(
                view('emails.estimate-link', compact('estimate'))->render(),
                function ($mail) use ($recipient, $subject, $estimate) {
                    $mail->to($recipient)->subject($subject);

                    if (filled($estimate->company->name)) {
                        $mail->from(
                            config('mail.from.address'),
                            $estimate->company->name
                        );
                    }
                }
            );

            $this->markSent($log, config('mail.default'));
        } catch (Throwable $e) {
            $this->fail($log, $e);
            throw $e;
        }

        return $log->refresh();
    }

    public function sendQuickBillEmail(QuickBill $quickBill): CommunicationLog
    {
        $quickBill->loadMissing(['company', 'client']);

        $recipient = trim((string) $quickBill->client->email);

        if ($recipient === '') {
            throw new RuntimeException('This customer does not have an email address.');
        }

        $subject = "{$quickBill->company->name}: {$quickBill->quick_bill_number}";
        $message = "Your Quick Bill is ready: {$quickBill->portalUrl()}";

        $log = $this->makeLog(
            company: $quickBill->company,
            client: $quickBill->client,
            quickBill: $quickBill,
            channel: 'email',
            recipient: $recipient,
            purpose: 'quick_bill',
            subject: $subject,
            message: $message,
        );

        try {
            Mail::html(
                view('emails.quick-bill-link', compact('quickBill'))->render(),
                function ($mail) use ($recipient, $subject, $quickBill) {
                    $mail->to($recipient)->subject($subject);

                    if (filled($quickBill->company->name)) {
                        $mail->from(
                            config('mail.from.address'),
                            $quickBill->company->name
                        );
                    }
                }
            );

            $this->markSent($log, config('mail.default'));
        } catch (Throwable $e) {
            $this->fail($log, $e);
            throw $e;
        }

        return $log->refresh();
    }

    public function sendEstimateSms(Estimate $estimate): CommunicationLog
    {
        $estimate->loadMissing(['company', 'client']);

        $this->assertSmsEntitled($estimate->company);

        $recipient = $this->normalizeUsPhone($estimate->client->phone);

        $message = "{$estimate->company->name}: Your estimate {$estimate->estimate_number} is ready to review: {$estimate->portalUrl()}";

        return $this->sendTelnyxSms(
            company: $estimate->company,
            client: $estimate->client,
            recipient: $recipient,
            message: $message,
            estimate: $estimate,
            purpose: 'estimate',
        );
    }

    public function sendQuickBillSms(QuickBill $quickBill): CommunicationLog
    {
        $quickBill->loadMissing(['company', 'client']);

        $this->assertSmsEntitled($quickBill->company);

        $recipient = $this->normalizeUsPhone($quickBill->client->phone);

        $message = "{$quickBill->company->name}: {$quickBill->quick_bill_number} for {$quickBill->money($quickBill->balanceDueCents())}: {$quickBill->portalUrl()}";

        return $this->sendTelnyxSms(
            company: $quickBill->company,
            client: $quickBill->client,
            recipient: $recipient,
            message: $message,
            quickBill: $quickBill,
            purpose: 'quick_bill',
        );
    }

    public function sendFreeformEmail(
        Company $company,
        Client $client,
        string $subject,
        string $message
    ): CommunicationLog {
        $recipient = trim((string) $client->email);

        if ($recipient === '') {
            throw new RuntimeException('This customer does not have an email address.');
        }

        $log = $this->makeLog(
            company: $company,
            client: $client,
            channel: 'email',
            recipient: $recipient,
            purpose: 'message',
            subject: $subject,
            message: $message,
        );

        try {
            Mail::html(
                view('emails.customer-message', compact('company', 'client', 'message'))->render(),
                function ($mail) use ($recipient, $subject, $company) {
                    $mail->to($recipient)->subject($subject);

                    if (filled($company->name)) {
                        $mail->from(
                            config('mail.from.address'),
                            $company->name
                        );
                    }
                }
            );

            $this->markSent($log, config('mail.default'));
        } catch (Throwable $e) {
            $this->fail($log, $e);
            throw $e;
        }

        return $log->refresh();
    }

    public function sendFreeformSms(
        Company $company,
        Client $client,
        string $message
    ): CommunicationLog {
        $this->assertSmsEntitled($company);

        $recipient = $this->normalizeUsPhone($client->phone);

        $body = "{$company->name}: {$message}";

        return $this->sendTelnyxSms(
            company: $company,
            client: $client,
            recipient: $recipient,
            message: $body,
            purpose: 'message',
        );
    }

    private function sendTelnyxSms(
        Company $company,
        ?Client $client,
        string $recipient,
        string $message,
        ?Estimate $estimate = null,
        ?QuickBill $quickBill = null,
        string $purpose = 'message',
    ): CommunicationLog {
        $apiKey = config('contractorspecialties.sms.telnyx_api_key');
        $from = config('contractorspecialties.sms.telnyx_from');
        $profileId = config('contractorspecialties.sms.telnyx_messaging_profile_id');

        if (blank($apiKey)) {
            throw new RuntimeException('TELNYX_API_KEY is not configured.');
        }

        if (blank($from) && blank($profileId)) {
            throw new RuntimeException(
                'Configure TELNYX_FROM_NUMBER or TELNYX_MESSAGING_PROFILE_ID before sending SMS.'
            );
        }

        $log = $this->makeLog(
            company: $company,
            client: $client,
            estimate: $estimate,
            quickBill: $quickBill,
            channel: 'sms',
            recipient: $recipient,
            purpose: $purpose,
            message: $message,
        );

        try {
            $payload = [
                'to' => $recipient,
                'text' => $message,
            ];

            if (filled($from)) {
                $payload['from'] = $from;
            } else {
                $payload['messaging_profile_id'] = $profileId;
            }

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->post('https://api.telnyx.com/v2/messages', $payload);

            if (! $response->successful()) {
                throw new RuntimeException(
                    "Telnyx SMS failed ({$response->status()}): ".$response->body()
                );
            }

            $log->update([
                'status' => 'sent',
                'provider' => 'telnyx',
                'provider_message_id' => data_get($response->json(), 'data.id'),
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            $this->fail($log, $e);
            throw $e;
        }

        return $log->refresh();
    }

    private function makeLog(
        Company $company,
        ?Client $client,
        string $channel,
        string $recipient,
        string $purpose,
        string $message,
        ?Estimate $estimate = null,
        ?QuickBill $quickBill = null,
        ?string $subject = null,
    ): CommunicationLog {
        return CommunicationLog::create([
            'company_id' => $company->id,
            'client_id' => $client?->id,
            'estimate_id' => $estimate?->id,
            'quick_bill_id' => $quickBill?->id,
            'channel' => $channel,
            'purpose' => $purpose,
            'recipient' => $recipient,
            'subject' => $subject,
            'status' => 'pending',
            'provider' => $channel === 'sms' ? 'telnyx' : config('mail.default'),
            'message' => $message,
        ]);
    }

    private function markSent(CommunicationLog $log, ?string $provider): void
    {
        $log->update([
            'status' => 'sent',
            'provider' => $provider,
            'sent_at' => now(),
        ]);
    }

    private function fail(CommunicationLog $log, Throwable $e): void
    {
        $log->update([
            'status' => 'failed',
            'error_message' => Str::limit($e->getMessage(), 5000),
        ]);
    }

    private function assertSmsEntitled(Company $company): void
    {
        if (! $company->hasHealthyStripeConnection()) {
            throw new RuntimeException(
                'SMS is available after the contractor has a healthy integrated payment connection.'
            );
        }
    }

    private function normalizeUsPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (strlen($digits) === 10) {
            return '+1'.$digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+'.$digits;
        }

        if (str_starts_with((string) $phone, '+') && strlen($digits) >= 10) {
            return '+'.$digits;
        }

        throw new RuntimeException(
            'This customer does not have a valid phone number for SMS.'
        );
    }
}
