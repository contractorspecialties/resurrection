@extends('layouts.app')
@section('title','Recurring Services — ContractorSpecialties')
@section('content')
<div class="page-head"><div><div class="kicker">Recurring Services</div><h1>Work that comes back.</h1><p>Set the service once. ContractorSpecialties generates the next Quick Bill when it comes due.</p></div><a class="btn btn-primary" href="{{ route('recurring-services.create') }}">+ Recurring Service</a></div>
<div class="card">
@forelse($services as $service)
<div class="list-row" style="align-items:flex-start"><div><strong>{{ $service->name }} — {{ $service->client->name }}</strong><div class="muted">{{ $service->money() }} • {{ ucfirst($service->frequency) }} • next {{ $service->next_bill_on->format('M j, Y') }} • <span class="badge">{{ $service->is_active ? 'Active' : 'Paused' }}</span></div>@if($service->auto_send_email)<div class="muted" style="margin-top:5px">Auto-email next bill when generated.</div>@endif</div><div style="display:flex;gap:8px;flex-wrap:wrap"><form method="POST" action="{{ route('recurring-services.generate',$service) }}">@csrf<button class="btn btn-primary" type="submit" @disabled(!$service->is_active)>Generate Now</button></form><a class="btn btn-secondary" href="{{ route('recurring-services.edit',$service) }}">Edit</a><form method="POST" action="{{ route('recurring-services.toggle',$service) }}">@csrf<button class="btn btn-secondary" type="submit">{{ $service->is_active ? 'Pause' : 'Resume' }}</button></form></div></div>
@empty<div class="empty">No recurring services yet.</div>@endforelse
</div>
@endsection
