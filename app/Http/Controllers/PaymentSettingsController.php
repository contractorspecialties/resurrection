<?php

namespace App\Http\Controllers;

use App\Services\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentSettingsController extends Controller
{
    public function index(Request $request, StripeConnectService $stripe): View
    {
        $company = $request->user()->company;

        if ($company->stripe_account_id) {
            try {
                $company = $stripe->refreshAccountStatus($company);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return view('payments.index', compact('company'));
    }

    public function connect(Request $request, StripeConnectService $stripe): RedirectResponse
    {
        $company = $stripe->createOrRefreshConnectedAccount(
            $request->user()->company,
            $request->user()->email
        );

        return redirect()->away($stripe->onboardingUrl($company));
    }

    public function refresh(Request $request, StripeConnectService $stripe): RedirectResponse
    {
        $company = $request->user()->company;

        abort_unless($company->stripe_account_id, 404);

        return redirect()->away($stripe->onboardingUrl($company));
    }

    public function returned(Request $request, StripeConnectService $stripe): RedirectResponse
    {
        $company = $stripe->refreshAccountStatus($request->user()->company);

        return redirect()
            ->route('payments.index')
            ->with('status', $company->hasHealthyStripeConnection()
                ? 'Stripe is connected and ready to accept payments.'
                : 'Stripe setup is saved. Stripe may still need a little more information before payments are enabled.');
    }
}
