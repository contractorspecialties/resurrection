<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\QuickBill;
use App\Services\CustomerMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class CustomerMessageController extends Controller
{
    public function estimateEmail(
        Request $request,
        Estimate $estimate,
        CustomerMessagingService $messaging
    ): RedirectResponse {
        $this->authorizeEstimate($request, $estimate);

        try {
            $messaging->sendEstimateEmail($estimate);

            if ($estimate->status === 'draft') {
                $estimate->update(['status' => 'sent']);
            }

            return back()->with('status', "Estimate emailed to {$estimate->client->email}.");
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'message' => 'Estimate email failed: '.$e->getMessage(),
            ]);
        }
    }

    public function estimateSms(
        Request $request,
        Estimate $estimate,
        CustomerMessagingService $messaging
    ): RedirectResponse {
        $this->authorizeEstimate($request, $estimate);

        try {
            $messaging->sendEstimateSms($estimate);

            if ($estimate->status === 'draft') {
                $estimate->update(['status' => 'sent']);
            }

            return back()->with('status', "Estimate text sent to {$estimate->client->phone}.");
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'message' => 'Estimate SMS failed: '.$e->getMessage(),
            ]);
        }
    }

    public function quickBillEmail(
        Request $request,
        QuickBill $quickBill,
        CustomerMessagingService $messaging
    ): RedirectResponse {
        $this->authorizeQuickBill($request, $quickBill);

        try {
            $messaging->sendQuickBillEmail($quickBill);

            return back()->with('status', "Quick Bill emailed to {$quickBill->client->email}.");
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'message' => 'Quick Bill email failed: '.$e->getMessage(),
            ]);
        }
    }

    public function quickBillSms(
        Request $request,
        QuickBill $quickBill,
        CustomerMessagingService $messaging
    ): RedirectResponse {
        $this->authorizeQuickBill($request, $quickBill);

        try {
            $messaging->sendQuickBillSms($quickBill);

            return back()->with('status', "Quick Bill text sent to {$quickBill->client->phone}.");
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'message' => 'Quick Bill SMS failed: '.$e->getMessage(),
            ]);
        }
    }

    private function authorizeEstimate(Request $request, Estimate $estimate): void
    {
        abort_unless($estimate->company_id === $request->user()->company_id, 404);
    }

    private function authorizeQuickBill(Request $request, QuickBill $quickBill): void
    {
        abort_unless($quickBill->company_id === $request->user()->company_id, 404);
    }
}
