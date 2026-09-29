<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\Estimate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EstimateController extends Controller
{
    public function index(Request $request): View
    {
        $estimates = $request->user()
            ->company
            ->estimates()
            ->with('client')
            ->latest()
            ->paginate(20);

        return view('estimates.index', compact('estimates'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $clients = $request->user()->company->clients()->orderBy('name')->get();

        if ($clients->isEmpty()) {
            return redirect()
                ->route('customers.create')
                ->with('status', 'Add a customer first, then we’ll build the estimate.');
        }

        return view('estimates.form', [
            'estimate' => new Estimate(),
            'clients' => $clients,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $company = $request->user()->company;

        $estimate = DB::transaction(function () use ($company, $data) {
            $lockedCompany = Company::query()->lockForUpdate()->findOrFail($company->id);

            $client = Client::query()
                ->where('company_id', $lockedCompany->id)
                ->findOrFail($data['client_id']);

            $totals = $this->calculateTotals($data);

            $estimate = $lockedCompany->estimates()->create([
                'client_id' => $client->id,
                'estimate_number' => 'EST-' . $lockedCompany->next_estimate_number,
                'portal_token' => Str::random(48),
                'status' => 'draft',
                'version' => 1,
                'scope_summary' => $data['scope_summary'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal_cents' => $totals['subtotal_cents'],
                'tax_cents' => $totals['tax_cents'],
                'total_cents' => $totals['total_cents'],
                'deposit_cents' => $totals['deposit_cents'],
                'tax_rate' => $totals['tax_rate'],
            ]);

            $this->replaceItems($estimate, $totals['items']);
            $lockedCompany->increment('next_estimate_number');

            return $estimate;
        });

        return redirect()
            ->route('estimates.show', $estimate)
            ->with('status', "{$estimate->estimate_number} is ready to review.");
    }

    public function show(Request $request, Estimate $estimate): View
    {
        $this->authorizeEstimate($request, $estimate);

        $estimate->load([
            'client',
            'items',
            'revisionRequests.client',
            'acceptances',
        ]);

        return view('estimates.show', compact('estimate'));
    }

    public function edit(Request $request, Estimate $estimate): View
    {
        $this->authorizeEstimate($request, $estimate);

        abort_unless(
            in_array($estimate->status, ['draft', 'revision_requested'], true),
            409,
            'Only draft estimates or estimates awaiting revision can be edited.'
        );

        $estimate->load('items');

        $clients = $request->user()->company->clients()->orderBy('name')->get();

        return view('estimates.form', compact('estimate', 'clients'));
    }

    public function update(Request $request, Estimate $estimate): RedirectResponse
    {
        $this->authorizeEstimate($request, $estimate);

        abort_unless(
            in_array($estimate->status, ['draft', 'revision_requested'], true),
            409,
            'Only draft estimates or estimates awaiting revision can be edited.'
        );

        $data = $this->validated($request);

        DB::transaction(function () use ($request, $estimate, $data) {
            $client = Client::query()
                ->where('company_id', $request->user()->company_id)
                ->findOrFail($data['client_id']);

            $totals = $this->calculateTotals($data);
            $wasRevision = $estimate->status === 'revision_requested';

            $estimate->update([
                'client_id' => $client->id,
                'status' => $wasRevision ? 'sent' : 'draft',
                'version' => $wasRevision ? $estimate->version + 1 : $estimate->version,
                'scope_summary' => $data['scope_summary'] ?? null,
                'notes' => $data['notes'] ?? null,
                'subtotal_cents' => $totals['subtotal_cents'],
                'tax_cents' => $totals['tax_cents'],
                'total_cents' => $totals['total_cents'],
                'deposit_cents' => $totals['deposit_cents'],
                'tax_rate' => $totals['tax_rate'],
                'sent_at' => $wasRevision ? now() : $estimate->sent_at,
                'accepted_at' => null,
            ]);

            $estimate->items()->delete();
            $this->replaceItems($estimate, $totals['items']);
        });

        return redirect()
            ->route('estimates.show', $estimate)
            ->with('status', "{$estimate->estimate_number} was updated.");
    }

    public function markSent(Request $request, Estimate $estimate): RedirectResponse
    {
        $this->authorizeEstimate($request, $estimate);

        if ($estimate->status === 'draft') {
            $estimate->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        return redirect()
            ->route('estimates.show', $estimate)
            ->with('status', "{$estimate->estimate_number} is ready for the customer.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'integer'],
            'scope_summary' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:25'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.details' => ['nullable', 'string', 'max:2000'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'items.*.is_taxable' => ['nullable', 'boolean'],
        ]);
    }

    private function calculateTotals(array $data): array
    {
        $taxRate = round((float) ($data['tax_rate'] ?? 0), 3);
        $items = [];
        $subtotal = 0;
        $taxableSubtotal = 0;

        foreach ($data['items'] as $position => $item) {
            $quantity = round((float) $item['quantity'], 2);
            $unitPriceCents = (int) round(((float) $item['unit_price']) * 100);
            $lineTotalCents = (int) round($quantity * $unitPriceCents);
            $taxable = (bool) ($item['is_taxable'] ?? false);

            $subtotal += $lineTotalCents;

            if ($taxable) {
                $taxableSubtotal += $lineTotalCents;
            }

            $items[] = [
                'description' => trim($item['description']),
                'details' => filled($item['details'] ?? null) ? trim($item['details']) : null,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'line_total_cents' => $lineTotalCents,
                'is_taxable' => $taxable,
                'position' => $position,
            ];
        }

        $taxCents = (int) round($taxableSubtotal * ($taxRate / 100));
        $totalCents = $subtotal + $taxCents;
        $depositCents = (int) round(((float) ($data['deposit_amount'] ?? 0)) * 100);

        if ($depositCents > $totalCents) {
            throw ValidationException::withMessages([
                'deposit_amount' => 'The deposit cannot be more than the estimate total.',
            ]);
        }

        return [
            'items' => $items,
            'subtotal_cents' => $subtotal,
            'tax_cents' => $taxCents,
            'total_cents' => $totalCents,
            'deposit_cents' => $depositCents,
            'tax_rate' => $taxRate,
        ];
    }

    private function replaceItems(Estimate $estimate, array $items): void
    {
        foreach ($items as $item) {
            $estimate->items()->create($item);
        }
    }

    private function authorizeEstimate(Request $request, Estimate $estimate): void
    {
        abort_unless(
            $estimate->company_id === $request->user()->company_id,
            404
        );
    }
}
