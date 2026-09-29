@extends('layouts.app')

@section('title', 'Price Book — ContractorSpecialties')

@section('content')
<div class="page-head">
    <div>
        <div class="kicker">Price Book</div>
        <h1>Stop pricing the same shit twice.</h1>
        <p>Save the services you sell every week and drop them into estimates in a click.</p>
    </div>

    <a class="btn btn-primary" href="{{ route('price-book.create') }}">+ Service / Item</a>
</div>

<div class="card">
    @forelse($items as $item)
        <div class="list-row">
            <div style="min-width:0">
                <strong>{{ $item->name }}</strong>

                <div class="muted">
                    @if($item->category)
                        {{ $item->category }} •
                    @endif
                    {{ $item->money() }}
                    • {{ $item->is_taxable ? 'Taxable' : 'Non-taxable' }}
                    •
                    <span class="badge">{{ $item->is_active ? 'Active' : 'Retired' }}</span>
                </div>

                @if($item->description)
                    <div class="muted" style="margin-top:5px">{{ \Illuminate\Support\Str::limit($item->description, 120) }}</div>
                @endif
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a class="btn btn-secondary" href="{{ route('price-book.edit', $item) }}">Edit</a>

                @if($item->is_active)
                    <form method="POST" action="{{ route('price-book.destroy', $item) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">Retire</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('price-book.restore', $item) }}">
                        @csrf
                        <button class="btn btn-secondary" type="submit">Restore</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="empty">
            Your Price Book is empty. Add the handful of things you quote constantly first.
        </div>
    @endforelse
</div>

@if(method_exists($items, 'links'))
    <div style="margin-top:18px">{{ $items->links() }}</div>
@endif
@endsection
