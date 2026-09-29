@extends('layouts.app')

@section('title', ($item->exists ? 'Edit' : 'New') . ' Price Book Item — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Price Book</div>
        <h1>{{ $item->exists ? 'Tune the price.' : 'Save it once.' }}</h1>
        <p>This is a reusable default, not a prison sentence. Estimates can still be edited job by job.</p>
    </div>
</div>

<form method="POST" action="{{ $item->exists ? route('price-book.update', $item) : route('price-book.store') }}">
    @csrf
    @if($item->exists)
        @method('PUT')
    @endif

    <div class="grid grid-2">
        <div class="card">
            <div class="field">
                <label for="name">Service / item name</label>
                <input id="name" name="name" value="{{ old('name', $item->name) }}" required
                       placeholder="Replace garbage disposal">
            </div>

            <div class="field">
                <label for="category">Category <span class="muted">(optional)</span></label>
                <input id="category" name="category" value="{{ old('category', $item->category) }}"
                       placeholder="Plumbing, Service Calls, Trim Work…">
            </div>

            <div class="field">
                <label for="description">Default description <span class="muted">(optional)</span></label>
                <textarea id="description" name="description"
                          placeholder="Remove existing unit, install customer-approved replacement, test for leaks, clean work area.">{{ old('description', $item->description) }}</textarea>
            </div>
        </div>

        <div class="card">
            <div class="field">
                <label for="unit_price">Default price</label>
                <input id="unit_price" name="unit_price" type="number" min="0" step="0.01"
                       value="{{ old('unit_price', $item->exists ? number_format($item->unit_price_cents / 100, 2, '.', '') : '') }}"
                       required>
            </div>

            <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px">
                <input style="width:auto" type="checkbox" name="is_taxable" value="1"
                       @checked(old('is_taxable', $item->is_taxable ?? true))>
                <span>Taxable by default</span>
            </label>

            <label style="display:flex;gap:10px;align-items:center">
                <input style="width:auto" type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $item->is_active ?? true))>
                <span>Active in Price Book</span>
            </label>
        </div>
    </div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:20px">
        <button class="btn btn-primary" type="submit">
            {{ $item->exists ? 'Save Changes' : 'Add to Price Book' }}
        </button>
        <a class="btn btn-secondary" href="{{ route('price-book.index') }}">Cancel</a>
    </div>
</form>
@endsection
