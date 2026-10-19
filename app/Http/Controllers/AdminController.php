<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function company(Company $company): View
    {
        $company->load('owner');

        $company->loadCount([
            'clients',
            'estimates',
            'quickBills',
            'payments',
        ]);

        $estimates = $company->estimates()
            ->with('client')
            ->latest()
            ->limit(10)
            ->get();

        $quickBills = $company->quickBills()
            ->with('client')
            ->latest()
            ->limit(10)
            ->get();

        $payments = $company->payments()
            ->latest()
            ->limit(15)
            ->get();

        $failedMessages = \Illuminate\Support\Facades\DB::table('communication_logs')
            ->where('company_id', $company->id)
            ->where('status', 'failed')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return view('admin.company', compact(
            'company',
            'estimates',
            'quickBills',
            'payments',
            'failedMessages'
        ));
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $companies = Company::query()
            ->with('owner')
            ->withCount([
                'clients',
                'estimates',
                'quickBills',
                'payments',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('state', 'like', "%{$search}%")
                        ->orWhereHas('owner', function ($owner) use ($search) {
                            $owner->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'companies' => Company::count(),
            'stripe_ready' => Company::query()
                ->whereNotNull('stripe_account_id')
                ->where('stripe_details_submitted', true)
                ->where('stripe_charges_enabled', true)
                ->where('stripe_payouts_enabled', true)
                ->count(),
            'payments_paid' => Payment::query()
                ->where('status', 'paid')
                ->count(),
            'payments_failed' => Payment::query()
                ->where('status', 'failed')
                ->count(),
            'recorded_platform_fees_cents' => Payment::query()
                ->where('status', 'paid')
                ->sum('platform_fee_cents'),
        ];

        return view('admin.index', compact(
            'companies',
            'stats',
            'search'
        ));
    }
}
