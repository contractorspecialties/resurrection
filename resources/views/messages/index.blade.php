@extends('layouts.app')

@section('title', 'Messages — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Messages</div>
        <h1>What did we send?</h1>
        <p>Email, SMS, estimate links and Quick Bills in one place.</p>
    </div>

    <a class="btn btn-primary" href="{{ route('messages.create') }}">+ Send Message</a>
</div>

<div class="card">
    @forelse($logs as $log)
        <div class="list-row" style="align-items:flex-start">
            <div style="min-width:0">
                <strong>
                    {{ strtoupper($log->channel) }}
                    @if($log->client)
                        → {{ $log->client->name }}
                    @else
                        → {{ $log->recipient }}
                    @endif
                </strong>

                <div class="muted" style="margin-top:4px">
                    {{ str_replace('_', ' ', ucfirst($log->purpose)) }}
                    • {{ $log->sent_at?->format('M j, Y g:i A') ?? $log->created_at->format('M j, Y g:i A') }}
                    • <span class="badge">{{ $log->status }}</span>
                </div>

                @if($log->subject)
                    <div style="margin-top:7px"><strong>{{ $log->subject }}</strong></div>
                @endif

                <div class="muted" style="margin-top:5px">
                    {{ \Illuminate\Support\Str::limit($log->message, 160) }}
                </div>

                @if($log->status === 'failed' && $log->error_message)
                    <div style="margin-top:7px;color:#8b2d24">
                        {{ \Illuminate\Support\Str::limit($log->error_message, 220) }}
                    </div>
                @endif
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @if($log->estimate)
                    <a class="btn btn-secondary" href="{{ route('estimates.show', $log->estimate) }}">Estimate</a>
                @endif

                @if($log->quickBill)
                    <a class="btn btn-secondary" href="{{ route('quick-bills.show', $log->quickBill) }}">Quick Bill</a>
                @endif
            </div>
        </div>
    @empty
        <div class="empty">No messages sent yet.</div>
    @endforelse
</div>

@if(method_exists($logs, 'links'))
    <div style="margin-top:18px">{{ $logs->links() }}</div>
@endif
@endsection
