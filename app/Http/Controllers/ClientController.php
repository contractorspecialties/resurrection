<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $clients = $request->user()
            ->company
            ->clients()
            ->orderBy('name')
            ->paginate(30);

        $archivedCount = Client::query()
            ->where('company_id', $request->user()->company_id)
            ->archived()
            ->count();

        return view('customers.index', compact('clients', 'archivedCount'));
    }

    public function archived(Request $request): View
    {
        $clients = Client::query()
            ->where('company_id', $request->user()->company_id)
            ->archived()
            ->orderBy('name')
            ->paginate(30);

        return view('customers.archived', compact('clients'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $client = $request->user()->company->clients()->create($data);

        return redirect()
            ->route('customers.edit', $client)
            ->with('status', "{$client->name} added.");
    }

    public function edit(Request $request, Client $client): View
    {
        $this->authorizeCompany($request, $client);
        abort_if($client->isArchived(), 404);

        return view('customers.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorizeCompany($request, $client);
        abort_if($client->isArchived(), 404);

        $client->update($this->validated($request));

        return back()->with('status', 'Customer updated.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorizeCompany($request, $client);

        $hasHistory = $client->estimates()->exists()
            || $client->quickBills()->exists()
            || $client->payments()->exists()
            || $client->recurringServices()->exists();

        if ($hasHistory) {
            $client->update(['archived_at' => now()]);

            return redirect()
                ->route('customers.index')
                ->with(
                    'status',
                    "{$client->name} was archived. Their estimate and payment history was preserved."
                );
        }

        $name = $client->name;
        $client->delete();

        return redirect()
            ->route('customers.index')
            ->with('status', "{$name} deleted.");
    }

    public function restore(Request $request, int $client): RedirectResponse
    {
        $client = Client::query()
            ->where('company_id', $request->user()->company_id)
            ->archived()
            ->findOrFail($client);

        $client->update(['archived_at' => null]);

        return redirect()
            ->route('customers.edit', $client)
            ->with('status', "{$client->name} restored.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address_line1' => ['nullable', 'string', 'max:190'],
            'address_line2' => ['nullable', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:60'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function authorizeCompany(Request $request, Client $client): void
    {
        abort_unless($client->company_id === $request->user()->company_id, 404);
    }
}
