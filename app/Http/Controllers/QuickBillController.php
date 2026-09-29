<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Company;
use App\Models\QuickBill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuickBillController extends Controller
{
    public function index(Request $request): View
    {
        $quickBills = $request->user()
            ->company
            ->quickBills()
            ->with('client')
            ->latest()
            ->paginate(20);

        return view('quick-bills.index', compact('quickBills'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $clients = $request->user()
            ->company
            ->clients()
            ->orderBy('name')
            ->get();

        if ($clients->isEmpty()) {
            return redirect()
                ->route('customers.create')
                ->with('status', 'Add the customer first, then Quick Bill the plumb bob.');
        }

        return view('quick-bills.create', compact('clients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'integer'],
            'description' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999'],
        ]);

        $company = $request->user()->company;

        $quickBill = DB::transaction(function () use ($company, $data) {
            $lockedCompany = Company::query()->lockForUpdate()->findOrFail($company->id);

            $client = Client::query()
                ->where('company_id', $lockedCompany->id)
                ->findOrFail($data['client_id']);

            $quickBill = $lockedCompany->quickBills()->create([
                'client_id' => $client->id,
                'quick_bill_number' => 'QB-' . $lockedCompany->next_quick_bill_number,
                'portal_token' => Str::random(48),
                'status' => 'payment_due',
                'description' => trim($data['description']),
                'details' => filled($data['details'] ?? null) ? trim($data['details']) : null,
                'amount_cents' => (int) round(((float) $data['amount']) * 100),
            ]);

            $lockedCompany->increment('next_quick_bill_number');

            return $quickBill;
        });

        return redirect()
            ->route('quick-bills.show', $quickBill)
            ->with('status', "{$quickBill->quick_bill_number} is ready to collect.");
    }

    public function show(Request $request, QuickBill $quickBill): View
    {
        abort_unless($quickBill->company_id === $request->user()->company_id, 404);

        $quickBill->load(['client', 'payments']);

        return view('quick-bills.show', compact('quickBill'));
    }
}
