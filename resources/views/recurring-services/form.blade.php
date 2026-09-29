@extends('layouts.app')
@section('title',($service->exists?'Edit':'New').' Recurring Service — ContractorSpecialties')
@section('content')
<div class="page-head"><div><div class="kicker">Recurring Service</div><h1>{{ $service->exists ? 'Keep it predictable.' : 'Set it once.' }}</h1><p>Create a repeatable service that generates a normal Quick Bill on schedule.</p></div></div>
<form method="POST" action="{{ $service->exists ? route('recurring-services.update',$service) : route('recurring-services.store') }}">@csrf @if($service->exists) @method('PUT') @endif
<div class="grid grid-2"><div class="card">
<div class="field"><label>Customer</label><select name="client_id" required>@foreach($clients as $client)<option value="{{ $client->id }}" @selected((string)old('client_id',$service->client_id)===(string)$client->id)>{{ $client->name }}</option>@endforeach</select></div>
<div class="field"><label>Start from Price Book <span class="muted">(optional)</span></label><select id="price_book_item_id" name="price_book_item_id"><option value="">Choose saved service…</option>@foreach($priceBookItems as $item)<option value="{{ $item->id }}" data-name="{{ $item->name }}" data-description="{{ $item->description }}" data-price="{{ number_format($item->unit_price_cents/100,2,'.','') }}" @selected((string)old('price_book_item_id',$service->price_book_item_id)===(string)$item->id)>{{ $item->category ? $item->category.' — ' : '' }}{{ $item->name }} — {{ $item->money() }}</option>@endforeach</select></div>
<div class="field"><label>Service name</label><input id="name" name="name" value="{{ old('name',$service->name) }}" required></div>
<div class="field"><label>Description</label><textarea id="description" name="description">{{ old('description',$service->description) }}</textarea></div>
</div><div class="card">
<div class="field"><label>Bill amount</label><input id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount',$service->exists?number_format($service->amount_cents/100,2,'.',''):'') }}" required></div>
<div class="field"><label>Frequency</label><select name="frequency">@foreach(['weekly'=>'Weekly','biweekly'=>'Every 2 weeks','monthly'=>'Monthly','quarterly'=>'Every 3 months','annually'=>'Yearly'] as $value=>$label)<option value="{{ $value }}" @selected(old('frequency',$service->frequency)===$value)>{{ $label }}</option>@endforeach</select></div>
<div class="field"><label>Next bill date</label><input name="next_bill_on" type="date" value="{{ old('next_bill_on',$service->next_bill_on?->format('Y-m-d')) }}" required></div>
<label style="display:flex;gap:10px;align-items:center;margin-bottom:18px"><input style="width:auto" type="checkbox" name="auto_send_email" value="1" @checked(old('auto_send_email',$service->auto_send_email))><span>Automatically email the Quick Bill when generated</span></label>
<label style="display:flex;gap:10px;align-items:center"><input style="width:auto" type="checkbox" name="is_active" value="1" @checked(old('is_active',$service->is_active??true))><span>Active</span></label>
</div></div><div style="margin-top:20px"><button class="btn btn-primary" type="submit">{{ $service->exists?'Save Changes':'Create Recurring Service' }}</button></div></form>
@endsection
@push('scripts')<script>(()=>{const p=document.getElementById('price_book_item_id');p?.addEventListener('change',()=>{const o=p.options[p.selectedIndex];if(!o?.value)return;document.getElementById('name').value=o.dataset.name||'';document.getElementById('description').value=o.dataset.description||'';document.getElementById('amount').value=o.dataset.price||'';});})();</script>@endpush
