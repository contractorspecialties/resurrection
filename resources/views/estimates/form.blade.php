@extends('layouts.app')

@section('title', ($estimate->exists ? 'Edit Estimate' : 'New Estimate') . ' — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Estimate</div>
        <h1>{{ $estimate->exists ? $estimate->estimate_number : 'Price the job.' }}</h1>
        <p>Clear scope. Clear price. No spreadsheet archaeology.</p>
    </div>
</div>

<form method="POST" action="{{ $estimate->exists ? route('estimates.update', $estimate) : route('estimates.store') }}">
    @csrf
    @if($estimate->exists)
        @method('PUT')
    @endif

    <div class="grid grid-2">
        <div class="card">
            <div class="field">
                <label for="client_id">Customer</label>
                <select id="client_id" name="client_id" required>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}"
                            @selected((string) old('client_id', $estimate->client_id) === (string) $client->id)>
                            {{ $client->name }}{{ $client->company_name ? ' — '.$client->company_name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="scope_summary">What are you doing?</label>
                <textarea id="scope_summary" name="scope_summary" placeholder="Replace damaged fascia, install new aluminum wrap, clean up and haul away debris…">{{ old('scope_summary', $estimate->scope_summary) }}</textarea>
            </div>

            <div class="field">
                <label for="notes">Customer notes / terms</label>
                <textarea id="notes" name="notes" placeholder="Schedule, exclusions, material notes, anything the customer should know…">{{ old('notes', $estimate->notes) }}</textarea>
            </div>
        </div>

        <div class="card">
            <div class="field">
                <label for="tax_rate">Tax rate %</label>
                <input id="tax_rate" name="tax_rate" type="number" min="0" max="25" step="0.001"
                       value="{{ old('tax_rate', $estimate->tax_rate ?? 0) }}">
                <div class="help">Applied only to line items marked taxable.</div>
            </div>

            <div class="field">
                <label for="deposit_amount">Deposit required</label>
                <input id="deposit_amount" name="deposit_amount" type="number" min="0" step="0.01"
                       value="{{ old('deposit_amount', $estimate->exists ? number_format($estimate->deposit_cents / 100, 2, '.', '') : '0.00') }}">
            </div>

            <div style="padding:18px;background:#f7f3ed;border-radius:12px">
                <div class="kicker">Live total</div>
                <div id="live-total" style="font-size:2.6rem;font-weight:950">$0.00</div>
                <div class="muted">Includes taxable items and the tax rate above.</div>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:20px">
        <div class="page-head" style="margin-bottom:16px;align-items:center">
            <div>
                <div class="kicker">Price Book</div>
                <h2 style="margin:5px 0 0">Use something you already priced.</h2>
            </div>

            <a class="btn btn-secondary" href="{{ route('price-book.create') }}" target="_blank" rel="noopener">
                + Add Price Book Item
            </a>
        </div>

        <div style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end">
            <div class="field" style="margin:0">
                <label for="price-book-picker">Saved service / item</label>
                <select id="price-book-picker">
                    <option value="">Loading Price Book…</option>
                </select>
                <div class="help">Adds the saved description, price and taxable setting. You can edit the line afterward.</div>
            </div>

            <button class="btn btn-primary" id="add-from-price-book" type="button" disabled>
                Add to Estimate →
            </button>
        </div>
    </div>

    <div class="card" style="margin-top:20px">
        <div class="page-head" style="margin-bottom:16px;align-items:center">
            <div>
                <div class="kicker">Line items</div>
                <h2 style="margin:5px 0 0">What are you charging for?</h2>
            </div>
            <button class="btn btn-secondary" type="button" id="add-line">+ Add Blank Line</button>
        </div>

        <div id="lines">
            @php
                $oldItems = old('items');
                $rows = $oldItems ?: ($estimate->exists
                    ? $estimate->items->map(fn($item) => [
                        'description' => $item->description,
                        'details' => $item->details,
                        'quantity' => $item->quantity,
                        'unit_price' => number_format($item->unit_price_cents / 100, 2, '.', ''),
                        'is_taxable' => $item->is_taxable ? 1 : 0,
                    ])->toArray()
                    : [[
                        'description' => '',
                        'details' => '',
                        'quantity' => 1,
                        'unit_price' => '',
                        'is_taxable' => 1,
                    ]]);
            @endphp

            @foreach($rows as $i => $row)
                <div class="estimate-line" data-index="{{ $i }}" style="border-top:1px solid var(--line);padding:20px 0">
                    <div style="display:grid;grid-template-columns:2fr .7fr .9fr auto;gap:12px;align-items:end">
                        <div class="field" style="margin:0">
                            <label>Description</label>
                            <input class="line-description" name="items[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}" required>
                        </div>

                        <div class="field" style="margin:0">
                            <label>Qty</label>
                            <input class="qty" type="number" min="0.01" step="0.01"
                                   name="items[{{ $i }}][quantity]" value="{{ $row['quantity'] ?? 1 }}" required>
                        </div>

                        <div class="field" style="margin:0">
                            <label>Unit price</label>
                            <input class="unit-price" type="number" min="0" step="0.01"
                                   name="items[{{ $i }}][unit_price]" value="{{ $row['unit_price'] ?? '' }}" required>
                        </div>

                        <button class="btn btn-danger remove-line" type="button">Remove</button>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end;margin-top:12px">
                        <div class="field" style="margin:0">
                            <label>Details <span class="muted">(optional)</span></label>
                            <input class="line-details" name="items[{{ $i }}][details]" value="{{ $row['details'] ?? '' }}">
                        </div>

                        <label style="display:flex;align-items:center;gap:8px;margin:0 0 11px">
                            <input class="taxable" style="width:auto" type="checkbox" value="1"
                                   name="items[{{ $i }}][is_taxable]"
                                   @checked((bool) ($row['is_taxable'] ?? false))>
                            Taxable
                        </label>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:20px">
        <button class="btn btn-primary" type="submit">
            {{ $estimate->exists ? 'Save Estimate' : 'Create Estimate' }}
        </button>
        <a class="btn btn-secondary" href="{{ route('estimates.index') }}">Cancel</a>
    </div>
</form>

<template id="line-template">
    <div class="estimate-line" data-index="__INDEX__" style="border-top:1px solid var(--line);padding:20px 0">
        <div style="display:grid;grid-template-columns:2fr .7fr .9fr auto;gap:12px;align-items:end">
            <div class="field" style="margin:0">
                <label>Description</label>
                <input class="line-description" name="items[__INDEX__][description]" required>
            </div>
            <div class="field" style="margin:0">
                <label>Qty</label>
                <input class="qty" type="number" min="0.01" step="0.01" name="items[__INDEX__][quantity]" value="1" required>
            </div>
            <div class="field" style="margin:0">
                <label>Unit price</label>
                <input class="unit-price" type="number" min="0" step="0.01" name="items[__INDEX__][unit_price]" required>
            </div>
            <button class="btn btn-danger remove-line" type="button">Remove</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end;margin-top:12px">
            <div class="field" style="margin:0">
                <label>Details <span class="muted">(optional)</span></label>
                <input class="line-details" name="items[__INDEX__][details]">
            </div>
            <label style="display:flex;align-items:center;gap:8px;margin:0 0 11px">
                <input class="taxable" style="width:auto" type="checkbox" value="1" name="items[__INDEX__][is_taxable]" checked>
                Taxable
            </label>
        </div>
    </div>
</template>
@endsection

@push('scripts')
<script>
(() => {
    const lines = document.getElementById('lines');
    const addButton = document.getElementById('add-line');
    const template = document.getElementById('line-template');
    const taxRate = document.getElementById('tax_rate');
    const total = document.getElementById('live-total');

    const picker = document.getElementById('price-book-picker');
    const addFromBook = document.getElementById('add-from-price-book');

    let nextIndex = {{ count($rows) }};
    let priceBook = [];

    function money(value) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(value || 0);
    }

    function recalc() {
        let subtotal = 0;
        let taxableSubtotal = 0;

        lines.querySelectorAll('.estimate-line').forEach(line => {
            const qty = parseFloat(line.querySelector('.qty')?.value || 0);
            const price = parseFloat(line.querySelector('.unit-price')?.value || 0);
            const lineTotal = qty * price;

            subtotal += lineTotal;

            if (line.querySelector('.taxable')?.checked) {
                taxableSubtotal += lineTotal;
            }
        });

        const rate = parseFloat(taxRate.value || 0) / 100;
        const tax = taxableSubtotal * rate;

        total.textContent = money(subtotal + tax);
    }

    function appendBlankLine() {
        const index = nextIndex++;
        const html = template.innerHTML.replaceAll('__INDEX__', index);
        lines.insertAdjacentHTML('beforeend', html);

        return lines.querySelector(`.estimate-line[data-index="${index}"]`);
    }

    function findReusableBlankLine() {
        const rows = [...lines.querySelectorAll('.estimate-line')];

        return rows.find(line => {
            const description = line.querySelector('.line-description')?.value?.trim() || '';
            const price = line.querySelector('.unit-price')?.value?.trim() || '';

            return description === '' && price === '';
        }) || null;
    }

    addButton.addEventListener('click', () => {
        appendBlankLine();
        recalc();
    });

    lines.addEventListener('click', event => {
        if (!event.target.classList.contains('remove-line')) {
            return;
        }

        const rows = lines.querySelectorAll('.estimate-line');

        if (rows.length === 1) {
            const line = rows[0];
            line.querySelector('.line-description').value = '';
            line.querySelector('.line-details').value = '';
            line.querySelector('.qty').value = '1';
            line.querySelector('.unit-price').value = '';
            line.querySelector('.taxable').checked = true;
            recalc();
            return;
        }

        event.target.closest('.estimate-line').remove();
        recalc();
    });

    lines.addEventListener('input', recalc);
    lines.addEventListener('change', recalc);
    taxRate.addEventListener('input', recalc);

    fetch(@json(route('price-book.options')), {
        headers: {
            'Accept': 'application/json'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Price Book request failed');
            }

            return response.json();
        })
        .then(data => {
            priceBook = data.items || [];
            picker.innerHTML = '<option value="">Choose a saved service or item…</option>';

            if (priceBook.length === 0) {
                picker.innerHTML = '<option value="">Price Book is empty</option>';
                return;
            }

            let currentCategory = null;

            priceBook.forEach(item => {
                if (item.category !== currentCategory) {
                    currentCategory = item.category;
                }

                const option = document.createElement('option');
                option.value = item.id;
                option.textContent =
                    `${item.category ? item.category + ' — ' : ''}${item.name} — $${item.unit_price}`;

                picker.appendChild(option);
            });

            addFromBook.disabled = false;
        })
        .catch(() => {
            picker.innerHTML = '<option value="">Could not load Price Book</option>';
        });

    addFromBook.addEventListener('click', () => {
        const selected = priceBook.find(item => String(item.id) === picker.value);

        if (!selected) {
            return;
        }

        const line = findReusableBlankLine() || appendBlankLine();

        line.querySelector('.line-description').value = selected.name;
        line.querySelector('.line-details').value = selected.description || '';
        line.querySelector('.qty').value = '1';
        line.querySelector('.unit-price').value = selected.unit_price;
        line.querySelector('.taxable').checked = Boolean(selected.is_taxable);

        recalc();
        line.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    recalc();
})();
</script>
@endpush
