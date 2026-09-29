<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;

        $company->loadCount([
            'clients',
            'estimates',
            'quickBills',
            'estimates as estimates_need_attention_count' => fn ($query) =>
                $query->whereIn('status', ['draft', 'revision_requested', 'deposit_due', 'balance_due']),
            'quickBills as quick_bills_due_count' => fn ($query) =>
                $query->where('status', 'payment_due'),
        ]);

        $recentClients = $company->clients()
            ->latest()
            ->limit(5)
            ->get();

        $recentEstimates = $company->estimates()
            ->with('client')
            ->latest()
            ->limit(6)
            ->get();

        $recentQuickBills = $company->quickBills()
            ->with('client')
            ->latest()
            ->limit(6)
            ->get();

        return view('dashboard', compact(
            'company',
            'recentClients',
            'recentEstimates',
            'recentQuickBills'
        ));
    }
}
