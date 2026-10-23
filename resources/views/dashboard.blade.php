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


<style>
.cs-mobile-toolbox { display:none; }

@media (max-width: 850px) {
    .cs-dashboard > :not(.cs-mobile-toolbox) {
        display:none !important;
    }

    .cs-mobile-toolbox {
        display:grid;
        gap:15px;
    }

    .cs-mobile-toolbox h1 {
        font-size:clamp(1.8rem,7.5vw,2.6rem);
        line-height:1.07;
        letter-spacing:-.04em;
        margin:0 0 4px;
        color:#12233a;
    }

    .cs-mobile-money {
        padding:20px;
        border:2px solid #cbd4dd;
        border-radius:17px;
        background:#fff;
    }

    .cs-mobile-money strong {
        display:block;
        font-size:2.5rem;
        line-height:1.15;
        font-weight:950;
        letter-spacing:-.04em;
        color:#12233a;
        font-variant-numeric:tabular-nums;
    }

    .cs-mobile-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:12px;
    }

    .cs-mobile-action {
        display:flex;
        flex-direction:column;
        justify-content:center;
        gap:9px;
        min-width:0;
        min-height:135px;
        padding:18px 14px;
        border:2px solid #12233a;
        border-radius:16px;
        background:#fff;
        color:#12233a;
        text-decoration:none;
        box-shadow:0 3px 0 rgba(18,35,58,.08);
    }

    .cs-mobile-action:focus-visible,
    .cs-mobile-action:hover {
        outline:3px solid #f3ae7f;
        outline-offset:2px;
    }

    .cs-mobile-action .cs-action-icon {
        color:#d95b16;
        font-size:2rem;
        line-height:1;
        font-weight:900;
    }

    .cs-mobile-action strong {
        font-size:clamp(.98rem,4.3vw,1.3rem);
        line-height:1.14;
        font-weight:950;
        overflow-wrap:break-word;
    }

    .cs-mobile-action small {
        color:#46576b;
        font-size:.8rem;
        line-height:1.3;
    }

    .cs-mobile-action.wide {
        grid-column:1 / -1;
        flex-direction:row;
        justify-content:flex-start;
        align-items:center;
        min-height:100px;
        padding:20px;
        background:#d95b16;
        color:#fff;
        border-color:#913307;
    }

    .cs-mobile-action.wide .cs-action-icon {
        color:#fff;
        font-size:2.2rem;
        flex:0 0 auto;
    }

    .cs-mobile-action.wide small {
        color:#fff;
    }

    .cs-mobile-action.wide .cs-action-text {
        display:grid;
        gap:5px;
    }

    .cs-mobile-action.utility {
        min-height:75px;
        border-color:#c4ced8;
        background:#f3f5f7;
    }

    .cs-mobile-more {
        border:2px solid #c4ced8;
        border-radius:14px;
        padding:14px;
        background:#fff;
    }

    .cs-mobile-more summary {
        cursor:pointer;
        padding:5px;
        font-weight:900;
        min-height:44px;
    }

    .cs-mobile-more a {
        display:block;
        padding:15px 10px;
        border-top:1px solid #dde3e8;
        font-weight:800;
        min-height:48px;
    }

    @media(max-width:380px) {
        .cs-mobile-action {
            padding:14px 11px;
        }
        .cs-mobile-action strong {
            font-size:1rem;
        }
    }
}

/* FINAL MOBILE TOOLBOX: scoped to phones and tablets only. */
@media(max-width:850px) {
    .cs-mobile-toolbox {
        gap:16px;
    }
    .cs-mobile-money {
        position:relative;
        overflow:hidden;
        padding:20px 22px;
    }
    .cs-mobile-money .muted {
        color:#647180;
        font-size:1rem;
        font-weight:750;
    }
    .cs-mobile-money strong {
        font-size:clamp(2.15rem,9vw,3rem);
    }
    .cs-mobile-grid {
        gap:11px;
    }
    .cs-mobile-action {
        align-items:flex-start;
        justify-content:center;
        min-height:118px;
        gap:12px;
        padding:17px;
    }
    .cs-mobile-action .cs-mobile-svg {
        width:42px;
        height:42px;
        flex:none;
        color:#e85d12;
    }
    .cs-mobile-action strong {
        font-size:clamp(.98rem,4.4vw,1.3rem);
        letter-spacing:-.025em;
    }
    .cs-mobile-action small {
        display:none;
    }
    .cs-mobile-action.wide {
        min-height:94px;
        gap:17px;
        background:#ee5e12;
        border-color:#17253b;
        box-shadow:0 3px 0 rgba(18,35,58,.09);
    }
    .cs-mobile-action.wide .cs-mobile-svg {
        width:48px;
        height:48px;
        color:#fff;
    }
    .cs-mobile-action.wide strong {
        font-size:clamp(1.2rem,5.2vw,1.65rem);
        color:#fff;
    }
    .cs-mobile-action.utility {
        min-height:68px;
        flex-direction:row;
        align-items:center;
        justify-content:flex-start;
        gap:12px;
        padding:12px;
    }
    .cs-mobile-action.utility .cs-mobile-svg {
        width:27px;
        height:27px;
        color:#667584;
    }
    .cs-mobile-action.utility strong {
        font-size:1rem;
    }
    .cs-mobile-toolbox h1 {
        max-width:16ch;
        font-size:clamp(2rem,8.5vw,3.2rem);
        font-weight:950;
    }
}
@media(max-width:360px) {
    .cs-mobile-action {
        padding:12px;
        min-height:104px;
    }
    .cs-mobile-action .cs-mobile-svg {
        width:35px;
        height:35px;
    }
}


/* PREMIUM MOBILE FINISH — mobile only */
@media(max-width:850px) {
    .cs-mobile-money {
        position:relative;
        padding-right:116px;
        min-height:108px;
        box-shadow:0 4px 12px rgba(18,35,58,.045);
    }

    .cs-money-coins {
        position:absolute;
        width:99px;
        height:86px;
        right:10px;
        top:50%;
        transform:translateY(-50%);
    }

    .cs-mobile-action .cs-mobile-svg {
        width:49px;
        height:49px;
        filter:drop-shadow(0 2px 1px rgba(18,35,58,.08));
    }

    .cs-mobile-action.wide {
        background:linear-gradient(
            160deg,
            #ff8a24 0%,
            #f36a12 52%,
            #e9570b 100%
        );
        box-shadow:
            0 4px 0 rgba(18,35,58,.14),
            inset 0 1px 0 rgba(255,255,255,.28);
    }

    .cs-mobile-action.wide:hover,
    .cs-mobile-action.wide:focus-visible {
        background:linear-gradient(
            160deg,
            #ff982f 0%,
            #f6781a 52%,
            #e9600e 100%
        );
    }

    .cs-mobile-action:not(.wide):not(.utility) {
        background:linear-gradient(
            155deg,
            #ffffff 0%,
            #ffffff 70%,
            #fafbfc 100%
        );
        box-shadow:0 3px 5px rgba(18,35,58,.06);
    }

    .cs-mobile-action.utility .cs-mobile-svg {
        width:29px;
        height:29px;
        filter:none;
    }
}

@media(max-width:360px) {
    .cs-mobile-money {
        padding-right:94px;
    }

    .cs-money-coins {
        width:78px;
        right:7px;
    }

    .cs-mobile-action .cs-mobile-svg {
        width:39px;
        height:39px;
    }
}

</style>

<div class="cs-dashboard">
    <section class="cs-mobile-toolbox" aria-label="Fast jobsite actions">
        <div>
            <div class="kicker">{{ $company->name }}</div>
            <h1>Fast actions for the jobsite</h1>
        </div>

        <div class="cs-mobile-money">
            <div class="muted">This month collected</div>
            <strong>{{ $money($collectedThisMonthCents) }}</strong>
            <svg class="cs-money-coins" viewBox="0 0 120 100"
                 fill="none" aria-hidden="true">
                <defs>
                    <linearGradient id="cs-coin-gradient" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#ffb23c"/>
                        <stop offset=".5" stop-color="#ff821b"/>
                        <stop offset="1" stop-color="#e85a0a"/>
                    </linearGradient>
                </defs>
                <g stroke="#12233a" stroke-width="4.5" stroke-linejoin="round">
                    <path d="M13 31v39c0 12 53 12 53 0V31"
                          fill="url(#cs-coin-gradient)"/>
                    <ellipse cx="39.5" cy="31" rx="26.5" ry="11"
                             fill="#ffbb55"/>
                    <path d="M13 45c0 12 53 12 53 0M13 58c0 12 53 12 53 0"
                          stroke="#fff0cc" stroke-width="3"/>
                    <path d="M60 59v22c0 11 47 11 47 0V59"
                          fill="url(#cs-coin-gradient)"/>
                    <ellipse cx="83.5" cy="59" rx="23.5" ry="10"
                             fill="#ffbd58"/>
                    <path d="M60 73c0 11 47 11 47 0"
                          stroke="#fff0cc" stroke-width="3"/>
                </g>
                <path d="M85 12l3-9M105 23l9-5"
                      stroke="#ed5d0c" stroke-width="5"
                      stroke-linecap="round"/>
            </svg>

        </div>

        <div class="cs-mobile-grid">
            <a class="cs-mobile-action wide"
               href="{{ route('estimates.create') }}">
                <svg class="cs-mobile-svg" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 2h7l5 5v15H5V2z"/><path d="M14 2v6h5M8 12h8M8 16h6"/></svg>
                <span class="cs-action-text">
                    <strong>Estimate → Invoice</strong>
                    <small>Start an estimate. Bill when the work is ready.</small>
                </span>
            </a>

            <a class="cs-mobile-action" href="{{ route('customers.index') }}">
                <svg class="cs-mobile-svg" aria-hidden="true"
viewBox="0 0 24 24" fill="none" stroke="#12233a"
stroke-width="2.2" stroke-linecap="round"
stroke-linejoin="round">
<circle cx="12" cy="7" r="3" fill="#f97316"/>
<path d="M5 21v-2a7 7 0 0 1 14 0v2"
      fill="#f97316"/>
<path d="M4 10a3 3 0 0 0-2 5M20 10a3 3 0 0 1 2 5"/>
</svg>
                <strong>Customers</strong>
                <small>Find or add a customer</small>
            </a>

            <a class="cs-mobile-action" href="{{ route('quick-bills.create') }}">
                <svg class="cs-mobile-svg" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h9l4 4v16H6z"/><path d="M15 2v5h4M9 11h5M9 15h3"/><circle cx="17" cy="17" r="5" fill="currentColor" stroke="none"/><path d="M17 14.5v5M18.5 15.4c-2-1.3-3.5.5-1.2 1.5s.7 2.7-1.2 1.6" stroke="#fff" stroke-width="1.1"/></svg>
                <strong>Quick Bill</strong>
                <small>Bill a small extra job</small>
            </a>

            <a class="cs-mobile-action wide"
               href="{{ route('estimates.index') }}">
                <svg class="cs-mobile-svg" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h5"/></svg>
                <span class="cs-action-text">
                    <strong>Collect Payment</strong>
                    <small>Find an estimate with a balance due</small>
                </span>
            </a>

            <a class="cs-mobile-action" href="{{ route('estimates.index') }}">
                <svg class="cs-mobile-svg" aria-hidden="true"
viewBox="0 0 24 24" fill="none" stroke="#12233a"
stroke-width="2.2" stroke-linecap="round"
stroke-linejoin="round">
<rect x="3" y="5" width="18" height="16" rx="2" fill="#fff"/>
<path d="M3 10h18V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"
      fill="#f97316"/>
<path d="M7 2v6M17 2v6"/>
<path d="M7 14h2M12 14h2M7 18h2M12 18h2"
      stroke-width="2.5"/>
</svg>
                <strong>Schedule</strong>
                <small>Find scheduled work</small>
            </a>

            <a class="cs-mobile-action" href="{{ route('estimates.index') }}">
                <svg class="cs-mobile-svg" aria-hidden="true"
viewBox="0 0 24 24" fill="none" stroke="#12233a"
stroke-width="2.2" stroke-linecap="round"
stroke-linejoin="round">
<path d="M3 17v-3a9 9 0 0 1 18 0v3H3z"
      fill="#ffad35"/>
<path d="M10 5V3h4v2" fill="#f97316"/>
<path d="M2 17h20M10 8v5"/>
</svg>
                <strong>Jobs</strong>
                <small>Review work in progress</small>
            </a>

            <a class="cs-mobile-action utility" href="{{ route('payments.index') }}">
                <svg class="cs-mobile-svg" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h5"/></svg><strong>Payments</strong>
                <small>Activity and settings</small>
            </a>

            <a class="cs-mobile-action utility" href="#cs-mobile-more" onclick="document.getElementById('cs-mobile-more').open=true">
                <svg class="cs-mobile-svg" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="5" cy="12" r="1.7" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.7" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1.7" fill="currentColor" stroke="none"/></svg><strong>More</strong>
                <small>Business tools</small>
            </a>
        </div>

        <details id="cs-mobile-more" class="cs-mobile-more">
            <summary>More business tools</summary>
            <a href="{{ route('price-book.index') }}">Price Book</a>
            <a href="{{ route('messages.index') }}">Messages</a>
            <a href="{{ route('recurring-services.index') }}">Recurring Services</a>
        </details>
    </section>



<style>
/* Desktop headquarters — mobile toolbox remains independent. */
@media(min-width:851px) {
    .cs-desktop-headquarters { display:grid;gap:20px; }
    .cs-desktop-hero {
        display:flex;
        justify-content:space-between;
        align-items:end;
        flex-wrap:wrap;
        gap:24px;
    }
    .cs-desktop-hero h1 {
        margin:8px 0 10px;
        font-size:clamp(2.3rem,4.5vw,3.8rem);
        line-height:1;
        text-transform:uppercase;
        letter-spacing:-.045em;
    }
    .cs-desktop-hero p {
        max-width:690px;
        color:#46576b;
        line-height:1.55;
        margin:0;
    }
    .cs-desktop-actions { display:flex;gap:10px;flex-wrap:wrap; }
    .cs-desktop-stats {
        display:grid;
        grid-template-columns:1.65fr repeat(3,minmax(0,1fr));
        gap:14px;
    }
    .cs-desktop-stats .card {
        padding:22px;
        min-width:0;
    }
    .cs-desktop-stats .cs-stat-figure {
        display:block;
        margin:15px 0 12px;
        font-size:clamp(2rem,3vw,3.1rem);
        font-weight:950;
        line-height:1;
        letter-spacing:-.045em;
        font-variant-numeric:tabular-nums;
    }
    .cs-desktop-stats .cs-stat-heading {
        font-weight:900;
        font-size:1rem;
        line-height:1.35;
    }
    .cs-desktop-stats .cs-stat-caption {
        color:#46576b;
        font-size:.9rem;
        line-height:1.45;
        min-height:42px;
    }
    .cs-desktop-stats .cs-stat-link {
        display:inline-flex;
        min-height:44px;
        align-items:center;
        font-weight:850;
        color:#a3410d;
    }
    .cs-desktop-stats .cs-featured-money {
        background:#eff7f2;
        border-color:#a9d1ba;
    }
    .cs-featured-money .cs-stat-figure { color:#176c45; }
    .cs-desktop-setup {
        border-top:5px solid #d95b16 !important;
    }
    .cs-desktop-setup .cs-section-head { align-items:center; }
    .cs-desktop-setup .cs-setup-list {
        display:grid;
        grid-template-columns:repeat(5,minmax(0,1fr));
        gap:10px;
    }
    .cs-desktop-setup .cs-step {
        align-items:flex-start;
        min-height:105px;
        padding:13px 10px;
    }
    .cs-desktop-setup .cs-step-symbol {
        width:28px;height:28px;flex:0 0 28px;
    }
    .cs-desktop-setup .cs-step span:last-child {
        font-size:.88rem;
    }
    .cs-desktop-setup .cs-step small {
        font-size:.78rem;
        margin-top:6px;
    }
    .cs-desktop-headquarters .cs-progress {
        margin:14px 0 18px;
    }
}
@media(min-width:851px) and (max-width:1100px) {
    .cs-desktop-stats {
        grid-template-columns:repeat(3,minmax(0,1fr));
    }
    .cs-desktop-stats .cs-featured-money {
        grid-column:1/-1;
    }
    .cs-desktop-setup .cs-setup-list {
        grid-template-columns:repeat(3,minmax(0,1fr));
    }
}
@media(max-width:850px) {
    .cs-desktop-headquarters { display:none !important; }
}
</style>


<style id="cs-desktop-polish-v1">
@media (min-width:851px) {

    .cs-desktop-headquarters {
        gap:18px;
    }

    .cs-desktop-hero {
        padding:10px 0 3px;
        align-items:center;
    }

    .cs-desktop-hero h1 {
        font-size:clamp(2.8rem,4.6vw,4.25rem);
        line-height:1.04;
        letter-spacing:-.055em;
        text-transform:none;
        font-weight:950;
        max-width:780px;
    }

    .cs-desktop-hero p {
        font-size:1.03rem;
        line-height:1.5;
        max-width:670px;
        color:#53677f;
    }

    .cs-desktop-actions {
        gap:12px;
    }

    .cs-desktop-actions .btn {
        min-height:54px;
        padding:15px 23px;
        border-radius:10px;
        font-weight:900;
        box-shadow:0 3px 8px rgba(20,40,65,.06);
    }

    .cs-desktop-stats {
        gap:13px;
        align-items:stretch;
    }

    .cs-desktop-stats .card {
        position:relative;
        padding:23px 21px 20px;
        border:1px solid #dce3ea;
        border-radius:14px;
        background:#fff;
        box-shadow:0 7px 22px rgba(17,35,60,.055);
        transition:box-shadow .15s ease;
    }

    .cs-desktop-stats .card:hover {
        box-shadow:0 10px 26px rgba(17,35,60,.09);
    }

    .cs-desktop-stats .cs-featured-money {
        background:linear-gradient(125deg,#f1faf5,#e8f5ed);
        border:1px solid #a9d8bf;
    }

    .cs-featured-money .kicker {
        color:#236548;
    }

    .cs-featured-money .cs-stat-figure {
        color:#087344;
        font-size:clamp(2.6rem,3.4vw,3.6rem);
        margin:20px 0 14px;
    }

    .cs-desktop-stats .cs-stat-heading {
        color:#142743;
        font-weight:900;
        font-size:1.08rem;
        line-height:1.35;
        min-height:2.7em;
    }

    .cs-desktop-stats .cs-stat-figure {
        color:#112643;
        font-size:clamp(2.25rem,3vw,3.2rem);
        line-height:1.05;
        margin:15px 0;
    }

    .cs-desktop-stats .cs-stat-caption {
        color:#53677e;
        font-size:.89rem;
        line-height:1.5;
        min-height:67px;
        margin-bottom:15px;
    }

    .cs-desktop-stats .cs-stat-link {
        display:flex;
        width:100%;
        align-items:center;
        justify-content:center;
        min-height:45px;
        padding:10px 12px;
        border:1px solid #bac8d7;
        border-radius:8px;
        background:#fff;
        color:#142743;
        font-size:.9rem;
        font-weight:900;
        text-decoration:none;
        text-align:center;
    }

    .cs-desktop-stats .cs-stat-link:hover,
    .cs-desktop-stats .cs-stat-link:focus-visible {
        background:#fff1e8;
        border-color:#d95b16;
        outline-offset:2px;
    }

    .cs-desktop-headquarters .card {
        overflow-wrap:anywhere;
    }

    .cs-desktop-setup {
        border:1px solid #e0e6ed !important;
        border-top:4px solid #e46117 !important;
        border-radius:14px;
        background:#fff;
        box-shadow:0 6px 22px rgba(17,35,60,.05);
    }

    .cs-desktop-setup .cs-step {
        border-radius:9px;
        background:#fff;
    }

}

@media (min-width:851px) and (max-width:1100px) {
    .cs-desktop-stats .cs-stat-heading {
        min-height:auto;
    }
    .cs-desktop-stats .cs-stat-caption {
        min-height:55px;
    }
}

@media (prefers-reduced-motion:reduce) {
    .cs-desktop-stats .card {
        transition:none;
    }
}
</style>


<style id="cs-desktop-polish-v2">
@media (min-width:851px) {

    .cs-heading-with-icon {
        display:flex;
        align-items:center;
        gap:13px;
        min-height:48px;
    }

    .cs-dashboard-icon {
        width:43px;
        height:43px;
        flex:0 0 43px;
        display:flex;
        align-items:center;
        justify-content:center;
        color:#df5914;
        background:#fff0e6;
        border-radius:11px;
    }

    .cs-dashboard-icon svg {
        width:26px;
        height:26px;
    }

    .cs-dashboard-icon.money {
        width:54px;
        height:54px;
        flex-basis:54px;
        border-radius:50%;
        background:#087344;
        color:white;
    }

    .cs-dashboard-icon.money svg {
        width:33px;
        height:33px;
    }

    .cs-desktop-stats .cs-stat-heading {
        min-height:0;
    }

    .cs-featured-money .cs-stat-figure {
        font-size:clamp(2.7rem,3.7vw,4rem);
    }

    .cs-activity {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:15px;
        align-items:stretch;
    }

    .cs-activity .card {
        min-width:0;
        padding:22px;
        background:#fff;
        border:1px solid #dce3eb;
        border-radius:14px;
        box-shadow:0 7px 22px rgba(17,35,60,.055);
    }

    .cs-activity .card h2 {
        color:#142743;
        font-size:1.3rem;
        letter-spacing:-.025em;
        margin:6px 0 16px;
    }

    .cs-activity .list-row {
        min-height:76px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:13px 0;
        border-bottom:1px solid #edf0f4;
    }

    .cs-activity .list-row:last-child {
        border-bottom:0;
    }

    .cs-activity .list-row strong {
        color:#142743;
    }

    .cs-activity .list-row a {
        flex:0 0 auto;
        font-weight:900;
    }

    .cs-activity .cs-panel-customers {
        grid-column:1 / -1;
    }

    .cs-panel-customers .list-row {
        display:inline-flex;
        vertical-align:top;
        width:calc(33.333% - 15px);
        margin-right:12px;
        border-bottom:0;
    }

    .cs-activity .kicker {
        font-size:.75rem;
        letter-spacing:.1em;
    }
}

@media (min-width:851px) and (max-width:1100px) {
    .cs-panel-customers .list-row {
        width:calc(50% - 15px);
    }
}
</style>

<style id="cs-desktop-action-icons-v1">
@media (min-width:851px) {
    .cs-desktop-actions .btn {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:10px;
        white-space:nowrap;
    }

    .cs-desktop-actions .btn svg {
        width:22px;
        height:22px;
        flex-shrink:0;
    }
}
</style>

<style id="cs-job-calendar-v1">
@media (min-width:851px) {

    /* Give the upper row the same dark action treatment. */
    .cs-desktop-stats .cs-stat-link {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        min-height:50px;
        margin-top:auto;
        padding:12px 15px;
        background:#152238;
        color:#fff;
        border:1px solid #152238;
        border-radius:9px;
        text-align:left;
    }

    .cs-desktop-stats .cs-stat-link::after {
        content:"→";
        display:grid;
        place-items:center;
        width:27px;
        height:27px;
        flex:0 0 27px;
        border-radius:50%;
        background:rgba(255,255,255,.15);
        font-size:1.2rem;
    }

    .cs-desktop-stats .cs-stat-link:hover,
    .cs-desktop-stats .cs-stat-link:focus-visible {
        background:#253b59;
        color:#fff;
        border-color:#253b59;
    }

    .cs-desktop-stats > .card {
        display:flex;
        flex-direction:column;
    }

    .cs-job-calendar {
        margin:10px 0 20px;
        padding:13px;
        border:1px solid #dce3eb;
        border-radius:12px;
        background:#f8fafc;
    }

    .cs-calendar-heading {
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:8px;
        margin-bottom:14px;
        color:#142743;
    }

    .cs-calendar-heading span {
        font-size:.75rem;
        color:#64748b;
    }

    .cs-calendar-grid {
        display:grid;
        grid-template-columns:repeat(7,minmax(0,1fr));
        gap:4px;
    }

    .cs-calendar-weekdays {
        margin-bottom:6px;
        color:#64748b;
        font-size:.72rem;
        font-weight:800;
        text-align:center;
    }

    .cs-calendar-day {
        position:relative;
        display:grid;
        place-items:center;
        min-width:0;
        min-height:33px;
        padding:4px 0 9px;
        border-radius:7px;
        font-size:.85rem;
        font-weight:750;
        color:#263d58;
        text-decoration:none;
    }

    .cs-calendar-day.today {
        outline:2px solid #142743;
        outline-offset:-2px;
    }

    .cs-calendar-day.has-job {
        background:#fff0e6;
        color:#a63c08;
        font-weight:950;
    }

    .cs-calendar-day.has-job:hover {
        background:#fbd5bd;
    }

    .cs-calendar-dot {
        position:absolute;
        bottom:3px;
        width:6px;
        height:6px;
        border-radius:50%;
        background:#df5914;
        animation:cs-job-pulse 1.8s ease-in-out infinite;
    }

    .cs-calendar-note {
        display:flex;
        align-items:center;
        gap:7px;
        flex-wrap:wrap;
        margin:12px 0 0;
        color:#64748b;
        font-size:.77rem;
    }

    .cs-calendar-legend {
        width:8px;
        height:8px;
        border-radius:50%;
        background:#df5914;
    }

    @keyframes cs-job-pulse {
        50% { opacity:.35; transform:scale(1.3); }
    }

    @media (prefers-reduced-motion:reduce) {
        .cs-calendar-dot { animation:none; }
    }
}
@media (max-width:850px) {
    .cs-job-calendar { display:none; }
}
</style>

<style id="cs-recurring-calendar-v1">
@media (min-width:851px) {
    .cs-calendar-agenda {
        display:grid;
        gap:5px;
        max-height:160px;
        overflow-y:auto;
        margin-top:12px;
        padding-top:10px;
        border-top:1px solid #dce3eb;
    }
    .cs-calendar-agenda a {
        display:block;
        padding:7px 9px;
        border-radius:7px;
        color:#142743;
        font-size:.83rem;
        font-weight:750;
        text-decoration:none;
    }
    .cs-calendar-agenda a:hover,
    .cs-calendar-agenda a:focus-visible {
        background:#fff0e6;
        text-decoration:underline;
    }
}
</style>
<div class="cs-desktop-headquarters">
    <header class="cs-desktop-hero">
        <div>
            <div class="kicker">{{ $company->name }}</div>
            <h1>What needs doing?</h1>
            <p>Customers, estimates, Quick Bills, and your money.
               Get in, get it done, and keep moving.</p>
        </div>
        <div class="cs-desktop-actions">
            <a class="btn btn-secondary" href="{{ route('estimates.create') }}">
                <svg viewBox="0 0 24 24" fill="none"
stroke="currentColor" stroke-width="2"
stroke-linecap="round" stroke-linejoin="round"
aria-hidden="true">
<path d="M6 2h9l5 5v15H6z"/>
<path d="M15 2v5h5M9 12h8M9 16h8"/>
</svg><span>New Estimate</span>
            </a>
            <a class="btn btn-primary" href="{{ route('quick-bills.create') }}">
                <svg viewBox="0 0 24 24" fill="none"
stroke="currentColor" stroke-width="2"
stroke-linecap="round" stroke-linejoin="round"
aria-hidden="true">
<rect x="5" y="2" width="14" height="20" rx="2"/>
<path d="M12 6v12M15 9c-1-2-6-2-6 1s6 1 6 4-5 3-6 1"/>
</svg><span>Quick Bill</span>
            </a>
        </div>
    </header>

    <div class="cs-desktop-stats">
        <section class="card cs-featured-money">
            <div class="cs-heading-with-icon"><span class="cs-dashboard-icon money"><svg viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2.2"
        stroke-linecap="round" stroke-linejoin="round"
        aria-hidden="true"><circle cx="12" cy="12" r="9"/>
        <path d="M15 8.5c-1-1-5-1.5-6 .5-2 3 6 2 6 5s-5 3-6 1.5M12 5.5v13"/></svg></span><div class="kicker">Collected this month</div></div>
            <strong class="cs-stat-figure">{{ $money($collectedThisMonthCents) }}</strong>
            <p class="cs-stat-caption">
                Recorded collections, less recorded refunds.
                This is money collected, not profit.
            </p>
            <a class="cs-stat-link" href="{{ route('payments.index') }}">
                Payment activity →
            </a>
        </section>

        <section class="card">
            <div class="cs-heading-with-icon"><span class="cs-dashboard-icon orange"><svg viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2.2"
        stroke-linecap="round" stroke-linejoin="round"
        aria-hidden="true"><circle cx="9" cy="8" r="3"/>
        <path d="M3 20v-2a6 6 0 0 1 12 0v2H3zM17 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1"/></svg></span><div class="cs-stat-heading">Customers</div></div>
            <strong class="cs-stat-figure">{{ $company->clients_count }}</strong>
            <p class="cs-stat-caption">People you're working with.</p>
            <a class="cs-stat-link" href="{{ route('customers.index') }}">
                View customers →
            </a>
        </section>

        <section class="card">
            <div class="cs-heading-with-icon"><span class="cs-dashboard-icon orange"><svg viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2.2"
        stroke-linecap="round" stroke-linejoin="round"
        aria-hidden="true"><path d="M5 2h10l4 4v16H5z"/>
        <path d="M15 2v5h4M9 12h6M9 16h6"/></svg></span><div class="cs-stat-heading">Estimates needing attention</div></div>
            <strong class="cs-stat-figure">
                {{ $company->estimates_need_attention_count }}
            </strong>
            <p class="cs-stat-caption">Drafts, revisions, and balances needing action.</p>
            <a class="cs-stat-link" href="{{ route('estimates.index') }}">
                View estimates →
            </a>
        </section>

        <section class="card">
            <div class="cs-heading-with-icon"><span class="cs-dashboard-icon orange"><svg viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2.2"
        stroke-linecap="round" stroke-linejoin="round"
        aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/>
        <path d="M2 10h20M6 15h5"/></svg></span><div class="cs-stat-heading">Quick Bills unpaid</div></div>
            <strong class="cs-stat-figure">
                {{ $company->quick_bills_due_count }}
            </strong>
            <p class="cs-stat-caption">Quick Bills still awaiting payment.</p>
            <a class="cs-stat-link" href="{{ route('quick-bills.index') }}">
                View Quick Bills →
            </a>
        </section>
    </div>

    @if($setupDone < 5)
    <section class="card cs-setup cs-desktop-setup" id="cs-setup-guide">
        <div class="cs-section-head">
            <div>
                <div class="kicker">Get set up</div>
                <h2>A few steps. Then you're rolling.</h2>
                <p class="muted" style="margin:0">
                    {{ $setupDone }} of 5 complete.
                    Online payment setup is optional.
                </p>
            </div>
            <button class="cs-hide" type="button" id="cs-hide-setup">
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
                <span>Business set up<small>You're ready to work.</small></span>
            </div>
            <a class="cs-step {{ $hasCustomers ? 'done' : '' }}"
               href="{{ route('customers.create') }}">
                <span class="cs-step-symbol">{{ $hasCustomers ? '✓' : '3' }}</span>
                <span>Add first customer
                    <small>{{ $hasCustomers ? 'Done' : 'Start your customer list.' }}</small>
                </span>
            </a>
            <a class="cs-step {{ $hasEstimates ? 'done' : '' }}"
               href="{{ route('estimates.create') }}">
                <span class="cs-step-symbol">{{ $hasEstimates ? '✓' : '4' }}</span>
                <span>Create first estimate
                    <small>{{ $hasEstimates ? 'Done' : 'Price your first job.' }}</small>
                </span>
            </a>
            <a class="cs-step {{ $hasPayments ? 'done' : '' }}"
               href="{{ route('payments.index') }}">
                <span class="cs-step-symbol">{{ $hasPayments ? '✓' : '5' }}</span>
                <span>Connect payments
                    <small>{{ $hasPayments ? 'Ready online' : 'Optional, when ready.' }}</small>
                </span>
            </a>
        </div>
    </section>
    @endif
</div>


<style id="cs-desktop-activity-v3">
@media (min-width:851px) {
    .cs-desktop-activity {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:14px;
        margin-top:20px;
        align-items:stretch;
    }

    .cs-desktop-activity > .card {
        min-width:0;
        padding:21px;
        border:1px solid #dce3eb;
        border-radius:14px;
        background:#fff;
        box-shadow:0 7px 22px rgba(17,35,60,.055);
    }

    .cs-desktop-activity .kicker {
        color:#d95b16;
        font-size:.76rem;
        letter-spacing:.1em;
        font-weight:900;
    }

    .cs-desktop-activity h2 {
        color:#142743;
        font-size:1.25rem;
        line-height:1.3;
        margin:8px 0 15px;
    }

    .cs-desktop-activity .list-row {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:13px 0;
        border-bottom:1px solid #edf0f4;
    }

    .cs-desktop-activity .list-row strong {
        color:#142743;
    }

    .cs-desktop-activity .list-row a {
        flex-shrink:0;
        font-weight:850;
    }

    .cs-desktop-activity .cs-empty {
        color:#53677e;
        margin:16px 0;
        line-height:1.5;
    }

    .cs-desktop-activity .cs-panel-action {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-height:42px;
        padding:10px 14px;
        margin-top:12px;
        border:1px solid #bac8d7;
        border-radius:8px;
        color:#142743;
        background:#fff;
        font-weight:900;
        text-decoration:none;
    }

    .cs-desktop-activity .cs-panel-action:hover {
        border-color:#d95b16;
        background:#fff1e8;
    }

    .cs-desktop-activity .cs-customers-panel {
        grid-column:1/-1;
    }

    .cs-desktop-activity .cs-customer-strip {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:12px;
    }

    .cs-desktop-activity .cs-customer-tile {
        display:flex;
        align-items:center;
        gap:12px;
        padding:12px;
        border:1px solid #e0e6ed;
        border-radius:10px;
        text-decoration:none;
        color:#142743;
        min-width:0;
    }

    .cs-desktop-activity .cs-customer-avatar {
        display:grid;
        place-items:center;
        width:40px;
        height:40px;
        flex:0 0 40px;
        background:#edf1f5;
        border-radius:50%;
        font-weight:900;
    }

    .cs-desktop-activity .cs-customer-tile strong {
        overflow-wrap:anywhere;
    }

    .cs-reminder-panel summary {
        cursor:pointer;
        min-height:45px;
        font-weight:900;
        color:#142743;
    }

    .cs-reminder-panel form {
        margin-top:12px;
    }
}

@media (min-width:851px) and (max-width:1100px) {
    .cs-desktop-activity {
        grid-template-columns:repeat(2,minmax(0,1fr));
    }

    .cs-desktop-activity .cs-reminder-panel {
        grid-column:1/-1;
    }

    .cs-desktop-activity .cs-customer-strip {
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}
</style>


<style id="cs-bottom-actions-v1">
@media (min-width:851px) {
    .cs-desktop-activity > .card:not(.cs-customers-panel) {
        display:flex;
        flex-direction:column;
        min-height:245px;
    }

    .cs-desktop-activity .cs-panel-action,
    .cs-desktop-activity .cs-reminder-open {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:14px;
        width:100%;
        min-height:50px;
        margin-top:auto;
        padding:13px 16px;
        background:#152238;
        color:#fff;
        border:1px solid #152238;
        border-radius:9px;
        font-weight:900;
        text-decoration:none;
        text-align:left;
        cursor:pointer;
    }

    .cs-desktop-activity .cs-panel-action:hover,
    .cs-desktop-activity .cs-reminder-open:hover {
        background:#253b59;
        color:#fff;
        border-color:#253b59;
    }

    .cs-desktop-activity .cs-panel-action:focus-visible,
    .cs-desktop-activity .cs-reminder-open:focus-visible {
        outline:3px solid #e77837;
        outline-offset:3px;
    }

    .cs-desktop-activity .cs-go-arrow {
        display:grid;
        place-items:center;
        width:27px;
        height:27px;
        flex:0 0 27px;
        border-radius:50%;
        background:rgba(255,255,255,.14);
        color:#fff;
        font-size:1.25rem;
        font-weight:900;
    }

    .cs-desktop-activity .cs-reminder-panel > details {
        margin-bottom:16px;
    }

    .cs-desktop-activity .cs-reminder-panel > details[open] {
        margin-bottom:18px;
    }

    /* Keep the customer-strip action consistent with the lower row. */
    .cs-desktop-activity .cs-customers-panel .cs-panel-action {
        width:auto;
        margin-top:14px;
    }
}
</style>
<div class="cs-desktop-activity">

    <section class="card cs-reminder-panel">

@php
    $calendarMonth = \Carbon\Carbon::now()->startOfMonth();
    $calendarDays = $calendarMonth->daysInMonth;
    $calendarOffset = $calendarMonth->dayOfWeek;
    $calendarToday = \Carbon\Carbon::today()->toDateString();

    $calendarJobs = $company->estimates()
        ->whereIn('status', ['active_job', 'balance_due'])
        ->whereNotNull('job_date')
        ->whereDate('job_date', '>=', $calendarMonth->toDateString())
        ->whereDate('job_date', '<=', $calendarMonth->copy()->endOfMonth()->toDateString())
        ->orderBy('job_date')
        ->get()
        ->groupBy(fn ($estimate) =>
            \Carbon\Carbon::parse($estimate->job_date)->day
        );
    $calendarVisits = \App\Models\RecurringService::query()
        ->where('company_id', $company->id)
        ->where('is_active', true)
        ->whereNotNull('next_visit_on')
        ->whereDate('next_visit_on', '>=', $calendarMonth->toDateString())
        ->whereDate('next_visit_on', '<=', $calendarMonth->copy()->endOfMonth()->toDateString())
        ->with('client')
        ->orderBy('next_visit_on')
        ->get()
        ->groupBy(fn ($service) => $service->next_visit_on->day);
@endphp

<div class="cs-job-calendar" aria-label="Scheduled jobs calendar">
    <div class="cs-calendar-heading">
        <strong>{{ $calendarMonth->format('F Y') }}</strong>
        <span>Scheduled work</span>
    </div>

    <div class="cs-calendar-grid cs-calendar-weekdays">
        @foreach(['S','M','T','W','T','F','S'] as $weekday)
            <span>{{ $weekday }}</span>
        @endforeach
    </div>

    <div class="cs-calendar-grid">
        @for($blank = 0; $blank < $calendarOffset; $blank++)
            <span aria-hidden="true"></span>
        @endfor

        @for($day = 1; $day <= $calendarDays; $day++)
            @php
                $date = $calendarMonth->copy()->day($day)->toDateString();
                $dayJobs = $calendarJobs->get($day, collect());
                $firstJob = $dayJobs->first();
                $dayVisits = $calendarVisits->get($day, collect());
                $firstVisit = $dayVisits->first();
                $scheduledCount = $dayJobs->count() + $dayVisits->count();
                $scheduledUrl = $firstJob
                    ? route('estimates.show', $firstJob)
                    : ($firstVisit ? route('recurring-services.edit', $firstVisit) : null);

            @endphp

            @if($scheduledUrl)
                <a class="cs-calendar-day has-job {{ $date === $calendarToday ? 'today' : '' }}"
                   href="{{ $scheduledUrl }}"
                   title="{{ $scheduledCount }} scheduled appointment(s)"
                   aria-label="{{ $scheduledCount }} scheduled appointment(s) on {{ $date }}. Open appointment.">
                    {{ $day }}
                    <span class="cs-calendar-dot" aria-hidden="true"></span>
                </a>
            @else
                <span class="cs-calendar-day {{ $date === $calendarToday ? 'today' : '' }}"
                      aria-label="{{ $date }}">
                    {{ $day }}
                </span>
            @endif
        @endfor
    </div>

    @if($calendarJobs->isNotEmpty() || $calendarVisits->isNotEmpty())
        <div class="cs-calendar-agenda">
            @foreach($calendarJobs as $day => $jobs)
                @foreach($jobs as $job)
                    <a href="{{ route('estimates.show', $job) }}">
                        {{ $job->job_date->format('M j') }}
                        @if($job->job_start_time)
                            · {{ \Carbon\Carbon::parse($job->job_start_time)->format('g:i A') }}
                        @endif
                        · {{ $job->estimate_number }}
                    </a>
                @endforeach
            @endforeach

            @foreach($calendarVisits as $day => $visits)
                @foreach($visits as $visit)
                    <a href="{{ route('recurring-services.edit', $visit) }}">
                        {{ $visit->next_visit_on->format('M j') }}
                        @if($visit->visit_start_time)
                            · {{ \Carbon\Carbon::parse($visit->visit_start_time)->format('g:i A') }}
                        @endif
                        · {{ $visit->name }}
                    </a>
                @endforeach
            @endforeach
        </div>
    @endif

    <p class="cs-calendar-note">
        <span class="cs-calendar-legend"></span>
        Orange dates have scheduled work.
        @if($calendarJobs->isEmpty() && $calendarVisits->isEmpty())
            No jobs scheduled this month.
        @endif
    </p>
</div>

        <details id="cs-desktop-reminder-settings" class="cs-reminders">
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
    <button type="button"
        class="cs-panel-action cs-reminder-open"
        aria-controls="cs-desktop-reminder-settings"
        aria-expanded="false"
        onclick="const d=document.getElementById('cs-desktop-reminder-settings');d.open=!d.open;this.setAttribute('aria-expanded',String(d.open));">
        <span>Configure reminders</span>
        <span class="cs-go-arrow" aria-hidden="true">→</span>
    </button>
    </section>

    <section class="card cs-estimates-panel">
        <div class="kicker">Recent estimates</div>
        <h2>Work in progress</h2>

        @forelse($recentEstimates as $estimate)
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
                <a href="{{ route('estimates.show', $estimate) }}">
                    Open →
                </a>
            </div>
        @empty
            <p class="cs-empty">
                No estimates yet. Your next job starts here.
            </p>
        @endforelse

        <a class="cs-panel-action" href="{{ route('estimates.create') }}">
            <span>+ New Estimate</span><span class="cs-go-arrow" aria-hidden="true">→</span>
        </a>
    </section>

    <section class="card cs-bills-panel">
        <div class="kicker">Recent Quick Bills</div>
        <h2>Small jobs. Fast payments.</h2>

        @forelse($recentQuickBills as $quickBill)
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
                <a href="{{ route('quick-bills.show', $quickBill) }}">
                    Open →
                </a>
            </div>
        @empty
            <p class="cs-empty">
                No Quick Bills yet. Bill for a small extra without
                creating a whole new estimate.
            </p>
        @endforelse

        <a class="cs-panel-action" href="{{ route('quick-bills.create') }}">
            <span>+ New Quick Bill</span><span class="cs-go-arrow" aria-hidden="true">→</span>
        </a>
    </section>

    <section class="card cs-customers-panel">
        <div class="kicker">Recent customers</div>
        <h2>People paying the bills</h2>

        <div class="cs-customer-strip">
            @forelse($recentClients as $client)
                <a class="cs-customer-tile"
                   href="{{ route('customers.edit', $client) }}">
                    <span class="cs-customer-avatar" aria-hidden="true">
                        {{ strtoupper(mb_substr($client->name, 0, 1)) }}
                    </span>
                    <strong>{{ $client->name }}</strong>
                </a>
            @empty
                <p class="cs-empty">
                    No customers yet. Add your first customer to get started.
                </p>
            @endforelse
        </div>

        <a class="cs-panel-action" href="{{ route('customers.index') }}">
            View all customers →
        </a>
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
