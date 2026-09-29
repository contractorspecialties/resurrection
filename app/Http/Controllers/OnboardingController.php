<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->onboarding_completed_at) {
            return redirect()->route('dashboard');
        }

        return view('setup');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'business_name' => ['nullable', 'string', 'max:160'],
            'trade' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'max:80'],
            'preferred_customer_contact' => ['required', 'in:text,email,phone'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $user = $request->user();

            $companyName = trim($data['business_name'] ?? '') ?: $data['name'];

            $company = Company::create([
                'name' => $companyName,
                'slug' => Company::uniqueSlug($companyName),
                'trade' => $data['trade'],
                'city' => $data['city'],
                'state' => $data['state'],
                'preferred_customer_contact' => $data['preferred_customer_contact'],
            ]);

            $user->forceFill([
                'name' => $data['name'],
                'company_id' => $company->id,
                'onboarding_completed_at' => now(),
            ])->save();
        });

        return redirect()
            ->route('dashboard')
            ->with('status', 'Your business is ready. Let’s put it to work.');
    }
}
