<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EstimateBalanceController extends Controller
{
    public function makeDue(Request $request, Estimate $estimate): RedirectResponse
    {
        abort_unless($estimate->company_id === $request->user()->company_id, 404);

        abort_unless(
            $estimate->status === 'active_job',
            409,
            'Only an active job can have its final balance requested.'
        );

        abort_if(
            $estimate->balanceDueCents() <= 0,
            409,
            'This estimate has no remaining balance.'
        );

        $estimate->update([
            'status' => 'balance_due',
        ]);

        return redirect()
            ->route('estimates.show', $estimate)
            ->with('status', "Final balance is now due for {$estimate->estimate_number}.");
    }
}
