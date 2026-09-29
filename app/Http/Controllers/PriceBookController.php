<?php

namespace App\Http\Controllers;

use App\Models\PriceBookItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceBookController extends Controller
{
    public function index(Request $request): View
    {
        $items = PriceBookItem::query()
            ->where('company_id', $request->user()->company_id)
            ->orderByDesc('is_active')
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(40);

        return view('price-book.index', compact('items'));
    }

    public function create(): View
    {
        $item = new PriceBookItem([
            'is_taxable' => true,
            'is_active' => true,
        ]);

        return view('price-book.form', compact('item'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $item = PriceBookItem::create([
            'company_id' => $request->user()->company_id,
            ...$data,
        ]);

        return redirect()
            ->route('price-book.edit', $item)
            ->with('status', "{$item->name} added to the Price Book.");
    }

    public function edit(Request $request, PriceBookItem $item): View
    {
        $this->authorizeCompany($request, $item);

        return view('price-book.form', compact('item'));
    }

    public function update(Request $request, PriceBookItem $item): RedirectResponse
    {
        $this->authorizeCompany($request, $item);

        $item->update($this->validated($request));

        return back()->with('status', 'Price Book item updated.');
    }

    public function destroy(Request $request, PriceBookItem $item): RedirectResponse
    {
        $this->authorizeCompany($request, $item);

        $item->update(['is_active' => false]);

        return redirect()
            ->route('price-book.index')
            ->with('status', "{$item->name} was retired from the Price Book.");
    }

    public function restore(Request $request, PriceBookItem $item): RedirectResponse
    {
        $this->authorizeCompany($request, $item);

        $item->update(['is_active' => true]);

        return redirect()
            ->route('price-book.index')
            ->with('status', "{$item->name} is active again.");
    }

    public function options(Request $request): JsonResponse
    {
        $items = PriceBookItem::query()
            ->where('company_id', $request->user()->company_id)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (PriceBookItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category,
                'description' => $item->description,
                'unit_price' => number_format($item->unit_price_cents / 100, 2, '.', ''),
                'is_taxable' => $item->is_taxable,
            ]);

        return response()->json(['items' => $items]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'category' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'is_taxable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => trim($data['name']),
            'category' => filled($data['category'] ?? null) ? trim($data['category']) : null,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'unit_price_cents' => (int) round(((float) $data['unit_price']) * 100),
            'is_taxable' => $request->boolean('is_taxable'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function authorizeCompany(Request $request, PriceBookItem $item): void
    {
        abort_unless($item->company_id === $request->user()->company_id, 404);
    }
}
