<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->is_internal_admin && !$user->company_id) {
            return redirect()->route('admin.index');
        }

        $company = $user->company;

        if (!$company) {
            abort(403, 'A company account is required.');
        }

        $company->loadCount([
            'clients',
            'estimates',
            'quickBills',
            'estimates as estimates_need_attention_count' => fn ($query) =>
                $query->whereIn('status', ['draft', 'revision_requested', 'deposit_due', 'balance_due']),
            'quickBills as quick_bills_due_count' => fn ($query) =>
                $query->where('status', 'payment_due'),
        ]);

        // Recorded payments collected during the current calendar month.
        // This is net recorded collection, not business profit.
        $monthStart = now()->startOfMonth();

        $collectedThisMonthCents = (int) \Illuminate\Support\Facades\DB::table('payments')
            ->where('company_id', $company->id)
            ->whereIn('status', ['paid', 'refunded'])
            ->where('paid_at', '>=', $monthStart)
            ->where('paid_at', '<', $monthStart->copy()->addMonth())
            ->sum(\Illuminate\Support\Facades\DB::raw(
                'GREATEST(amount_cents - COALESCE(refunded_amount_cents, 0), 0)'
            ));

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
            'recentQuickBills',
            'collectedThisMonthCents'
        ));
    }

    public function updateJobReminders(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'job_reminder_channel' => [
                'required',
                Rule::in(['off', 'email', 'sms', 'both']),
            ],
            'job_reminder_phone' => [
                'nullable',
                'string',
                'max:40',
            ],
        ]);

        if (
            in_array($data['job_reminder_channel'], ['sms', 'both'], true)
            && blank($data['job_reminder_phone'] ?? null)
        ) {
            return back()
                ->withErrors([
                    'job_reminder_phone' => 'Add a mobile number for SMS reminders.',
                ])
                ->withInput();
        }

        $request->user()->company->update([
            'job_reminder_channel' => $data['job_reminder_channel'],
            'job_reminder_phone' => filled($data['job_reminder_phone'] ?? null)
                ? trim($data['job_reminder_phone'])
                : null,
        ]);

        return back()->with('status', 'Job reminder settings saved.');
    }
}
