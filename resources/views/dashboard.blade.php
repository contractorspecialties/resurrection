@extends('layouts.app')

@section('title', 'Dashboard — ContractorSpecialties')

@section('content')
@php
    $hasCustomers = $company->clients_count > 0;
    $hasEstimates = $company->estimates_count > 0;
    $hasPayments = (bool) (
        $company->stripe_account_id
        && $company->stripe_charges_enabled
        && $company->stripe_payouts_enabled
    );

    $setupDone = 2
        + (int) $hasCustomers
        + (int) $hasEstimates
        + (int) $hasPayments;

    $attentionCount = $company->estimates_need_attention_count
        + $company->quick_bills_due_count;

    $hasActivity = $recentClients->isNotEmpty()
        || $recentEstimates->isNotEmpty()
        || $recentQuickBills->isNotEmpty();

    $money = fn ($cents) => '$' . number_format($cents / 100, 2);
@endphp

<style>
.cs-dashboard{display:grid;gap:22px}
.cs-dashboard .card{
    border:2px solid #d5dce3;
    box-shadow:0 5px 20px rgba(18,35,58,.035);
}
.cs-dashboard .btn{
    min-height:52px;
    padding:11px 20px;
    font-size:1rem;
}
.cs-dash-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:22px;
    flex-wrap:wrap;
}
.cs-dash-top h1{
    font-size:clamp(2rem,4vw,3.2rem);
    line-height:1;
    letter-spacing:-.04em;
    text-transform:uppercase;
    margin:8px 0;
}
.cs-dash-top p{color:#46576b;margin:8px 0}
.cs-dash-actions{display:flex;flex-wrap:wrap;gap:10px}
.cs-pulse{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
}
.cs-pulse .card{padding:22px}
.cs-metric{
    display:block;
    margin:13px 0 9px;
    font-size:clamp(2rem,3.4vw,3rem);
    line-height:1;
    font-weight:950;
    letter-spacing:-.04em;
    font-variant-numeric:tabular-nums;
}
.cs-metric-help{color:#46576b;font-size:.92rem;line-height:1.5}
.cs-setup{border-top:6px solid #d95b16 !important}
.cs-section-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    flex-wrap:wrap;
    gap:12px;
}
.cs-section-head h2{margin:6px 0 9px;font-size:1.55rem}
.cs-hide{
    background:#fff;
    color:#31445a;
    border:1px solid #9aa5b1;
    border-radius:9px;
    padding:11px 15px;
    font-weight:800;
    cursor:pointer;
    min-height:44px;
}
.cs-progress{
    background:#e1e6eb;
    height:12px;
    border-radius:20px;
    overflow:hidden;
    margin:16px 0 24px;
}
.cs-progress span{
    display:block;
    height:100%;
    background:#176c45;
    border-radius:20px;
}
.cs-setup-list{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:12px;
}
.cs-step{
    display:flex;
    align-items:center;
    gap:13px;
    min-height:68px;
    padding:14px;
    border:1px solid #cbd4dd;
    border-radius:12px;
    background:#fff;
    color:#12233a;
    font-weight:800;
}
.cs-step.done{background:#f0f7f2;border-color:#b6d4bf}
.cs-step:not(.done):hover{border-color:#d95b16}
.cs-step-symbol{
    width:34px;height:34px;
    flex:0 0 34px;
    border:2px solid #768594;
    border-radius:50%;
    display:grid;
    place-items:center;
    font-size:1rem;
}
.cs-step.done .cs-step-symbol{
    background:#176c45;
    color:#fff;
    border-color:#176c45;
}
.cs-step small{
    display:block;
    color:#46576b;
    font-size:.82rem;
    font-weight:500;
    line-height:1.4;
    margin-top:3px;
}
.cs-activity{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:18px;
}
.cs-activity h2{margin:7px 0 14px;font-size:1.3rem}
.cs-activity .list-row a{
    color:#a3410d;
    font-weight:850;
    white-space:nowrap;
}
.cs-reminders summary{
    cursor:pointer;
    font-size:1.1rem;
    font-weight:900;
    padding:4px 0;
}
.cs-reminders[open] summary{margin-bottom:18px}
.cs-reminders p{color:#46576b;line-height:1.5}
@media(max-width:850px){
    .cs-pulse{grid-template-columns:1fr}
    .cs-setup-list,.cs-activity{grid-template-columns:1fr}
}
@media(max-width:620px){
    .cs-dash-actions,.cs-dash-actions .btn{width:100%}
    .cs-dashboard .card{padding:20px}
    .cs-dash-top h1{font-size:2.2rem}
}
</style>

<div class="cs-dashboard">

    <div class="cs-dash-top">
        <div>
            <div class="kicker">{{ $company->name }}</div>
            <h1>Let's get to work.</h1>
            <p>Here's where your business stands today.</p>
        </div>

        <div class="cs-dash-actions">
            <a class="btn btn-primary" href="{{ route('estimates.create') }}">
                + New Estimate
            </a>
            <a class="btn btn-secondary" href="{{ route('customers.create') }}">
                + Customer
            </a>
            <a class="btn btn-secondary" href="{{ route('quick-bills.create') }}">
                + Quick Bill
            </a>
        </div>
    </div>

    <div class="cs-pulse">
        <section class="card">
            <div class="kicker">Collected this month</div>
            <strong class="cs-metric">{{ $money($collectedThisMonthCents) }}</strong>
            <div class="cs-metric-help">
                Recorded payments, less recorded refunds.
                Not business profit.
            </div>
        </section>

        <section class="card">
            <div class="kicker">Needs attention</div>
            <strong class="cs-metric">{{ $attentionCount }}</strong>
            <div class="cs-metric-help">
                Estimates needing action and unpaid Quick Bills.
            </div>
            @if($attentionCount > 0)
                <a href="{{ route('estimates.index') }}"
                   style="display:inline-block;margin-top:12px;font-weight:850;color:#a3410d">
                    Review work →
                </a>
            @endif
        </section>

        <section class="card">
            <div class="kicker">Customers</div>
            <strong class="cs-metric">{{ $company->clients_count }}</strong>
            <div class="cs-metric-help">
                People you're working with.
            </div>
            @if($company->clients_count > 0)
                <a href="{{ route('customers.index') }}"
                   style="display:inline-block;margin-top:12px;font-weight:850;color:#a3410d">
                    View customers →
                </a>
            @endif
        </section>
    </div>

    @if($setupDone < 5)
    <section class="card cs-setup" id="cs-setup-guide">
        <div class="cs-section-head">
            <div>
                <div class="kicker">Get set up</div>
                <h2>A few steps. Then you're rolling.</h2>
                <p class="muted" style="margin:0">
                    {{ $setupDone }} of 5 complete.
                    Payment setup is optional.
                </p>
            </div>
            <button type="button" class="cs-hide" id="cs-hide-setup">
                Hide setup guide
            </button>
        </div>

        <div class="cs-progress"
             role="progressbar"
             aria-label="Setup progress"
             aria-valuenow="{{ $setupDone }}"
             aria-valuemin="0"
             aria-valuemax="5">
            <span style="width:{{ $setupDone * 20 }}%"></span>
        </div>

        <div class="cs-setup-list">
            <div class="cs-step done">
                <span class="cs-step-symbol">✓</span>
                <span>Account created<small>You're signed in.</small></span>
            </div>

            <div class="cs-step done">
                <span class="cs-step-symbol">✓</span>
                <span>Business set up<small>Ready to get started.</small></span>
            </div>

            <a class="cs-step {{ $hasCustomers ? 'done' : '' }}"
               href="{{ route('customers.create') }}">
                <span class="cs-step-symbol">{{ $hasCustomers ? '✓' : '3' }}</span>
                <span>Add your first customer
                    <small>{{ $hasCustomers ? 'Done' : 'Start with your first customer.' }}</small>
                </span>
            </a>

            <a class="cs-step {{ $hasEstimates ? 'done' : '' }}"
               href="{{ route('estimates.create') }}">
                <span class="cs-step-symbol">{{ $hasEstimates ? '✓' : '4' }}</span>
                <span>Create your first estimate
                    <small>{{ $hasEstimates ? 'Done' : 'Put a price on the work.' }}</small>
                </span>
            </a>

            <a class="cs-step {{ $hasPayments ? 'done' : '' }}"
               href="{{ route('payments.index') }}">
                <span class="cs-step-symbol">{{ $hasPayments ? '✓' : '5' }}</span>
                <span>Connect online payments
                    <small>{{ $hasPayments ? 'Ready to collect online' : 'Optional — set this up when ready.' }}</small>
                </span>
            </a>
        </div>
    </section>
    @endif

    @if($hasActivity)
    <div class="cs-activity">
        @if($recentEstimates->isNotEmpty())
        <section class="card">
            <div class="kicker">Work in progress</div>
            <h2>Recent estimates</h2>

            @foreach($recentEstimates as $estimate)
                <div class="list-row">
                    <div>
                        <strong>
                            {{ $estimate->estimate_number }}
                            — {{ $estimate->client?->name ?? 'Customer unavailable' }}
                        </strong>
                        <div class="muted">
                            {{ $estimate->money($estimate->total_cents) }}
                            · {{ str_replace('_', ' ', $estimate->status) }}
                        </div>
                    </div>
                    <a href="{{ route('estimates.show', $estimate) }}">Open →</a>
                </div>
            @endforeach
        </section>
        @endif

        @if($recentQuickBills->isNotEmpty())
        <section class="card">
            <div class="kicker">Ready to collect</div>
            <h2>Recent Quick Bills</h2>

            @foreach($recentQuickBills as $quickBill)
                <div class="list-row">
                    <div>
                        <strong>
                            {{ $quickBill->quick_bill_number }}
                            — {{ $quickBill->client?->name ?? 'Customer unavailable' }}
                        </strong>
                        <div class="muted">
                            {{ $quickBill->money($quickBill->amount_cents) }}
                            · {{ str_replace('_', ' ', $quickBill->status) }}
                        </div>
                    </div>
                    <a href="{{ route('quick-bills.show', $quickBill) }}">Open →</a>
                </div>
            @endforeach
        </section>
        @endif

        @if($recentClients->isNotEmpty())
        <section class="card">
            <div class="kicker">People and projects</div>
            <h2>Recent customers</h2>

            @foreach($recentClients as $client)
                <div class="list-row">
                    <div>
                        <strong>{{ $client->name }}</strong>
                        <div class="muted">
                            {{ $client->phone ?: $client->email ?: 'No contact info yet' }}
                        </div>
                    </div>
                    <a href="{{ route('customers.edit', $client) }}">Open →</a>
                </div>
            @endforeach
        </section>
        @endif
    </div>
    @endif

    <section class="card">
        <details class="cs-reminders">
            <summary>Morning job reminders — settings</summary>
            <p>
                Want a reminder when you've got a job scheduled?
                Choose email, SMS, both, or off.
            </p>

            <form method="POST" action="{{ route('dashboard.job-reminders') }}">
                @csrf

                <div class="grid grid-2">
                    <div class="field">
                        <label for="job_reminder_channel">Send reminder by</label>
                        <select id="job_reminder_channel" name="job_reminder_channel">
                            @foreach([
                                'off' => 'Off',
                                'email' => 'Email',
                                'sms' => 'SMS',
                                'both' => 'Email + SMS',
                            ] as $value => $label)
                                <option value="{{ $value }}"
                                    @selected(old('job_reminder_channel', $company->job_reminder_channel ?? 'email') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label for="job_reminder_phone">Mobile number for SMS</label>
                        <input
                            id="job_reminder_phone"
                            name="job_reminder_phone"
                            value="{{ old('job_reminder_phone', $company->job_reminder_phone) }}"
                            placeholder="(555) 555-1212"
                        >
                    </div>
                </div>

                <button class="btn btn-secondary" type="submit">
                    Save reminder settings
                </button>
            </form>
        </details>
    </section>

</div>

<script>
(function () {
    var guide = document.getElementById('cs-setup-guide');
    var hide = document.getElementById('cs-hide-setup');

    if (!guide || !hide) return;

    // Browser-specific dismissal. No new database fields or endpoints.
    var key = 'cs-setup-hidden-company-{{ $company->id }}';

    try {
        if (localStorage.getItem(key) === '1') {
            guide.hidden = true;
        }
    } catch (e) {}

    hide.addEventListener('click', function () {
        guide.hidden = true;
        try { localStorage.setItem(key, '1'); } catch (e) {}
    });
})();
</script>
@endsection
