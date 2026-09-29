<?php

namespace App\Http\Controllers;

use App\Models\Estimate;
use App\Models\EstimateAcceptance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function show(string $token): View
    {
        $estimate = $this->estimateForToken($token);

        $estimate->load([
            'company',
            'client',
            'items',
            'revisionRequests',
            'acceptances',
        ]);

        return view('portal.show', compact('estimate'));
    }

    public function requestRevision(Request $request, string $token): RedirectResponse
    {
        $estimate = $this->estimateForToken($token);

        abort_unless(
            in_array($estimate->status, ['sent', 'revision_requested'], true),
            409,
            'This estimate is not currently open for revisions.'
        );

        $data = $request->validate([
            'message' => ['required', 'string', 'min:3', 'max:3000'],
        ]);

        DB::transaction(function () use ($estimate, $data) {
            $estimate->revisionRequests()->create([
                'client_id' => $estimate->client_id,
                'estimate_version' => $estimate->version,
                'message' => trim($data['message']),
                'requested_at' => now(),
            ]);

            $estimate->update([
                'status' => 'revision_requested',
            ]);
        });

        return redirect()
            ->route('portal.show', $estimate->portal_token)
            ->with('portal_status', 'Your change request was sent to the contractor.');
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $estimate = $this->estimateForToken($token);

        abort_unless(
            $estimate->status === 'sent',
            409,
            'This estimate is not currently available for acceptance.'
        );

        $data = $request->validate([
            'signature_name' => ['required', 'string', 'min:2', 'max:160'],
            'agree' => ['accepted'],
        ]);

        DB::transaction(function () use ($request, $estimate, $data) {
            $estimate = Estimate::query()
                ->lockForUpdate()
                ->with(['company', 'client', 'items'])
                ->findOrFail($estimate->id);

            if ($estimate->status !== 'sent') {
                abort(409, 'This estimate changed before acceptance. Please review it again.');
            }

            $estimate->acceptances()->create([
                'company_id' => $estimate->company_id,
                'client_id' => $estimate->client_id,
                'estimate_version' => $estimate->version,
                'signature_name' => trim($data['signature_name']),
                'accepted_at' => now(),
                'snapshot' => $estimate->acceptanceSnapshot(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $estimate->update([
                'status' => $estimate->deposit_cents > 0 ? 'deposit_due' : 'active_job',
                'accepted_at' => now(),
            ]);
        });

        return redirect()
            ->route('portal.show', $estimate->portal_token)
            ->with('portal_status', $estimate->deposit_cents > 0
                ? 'Estimate accepted. Your deposit is now due.'
                : 'Estimate accepted. The job is ready to move forward.');
    }

    private function estimateForToken(string $token): Estimate
    {
        return Estimate::query()
            ->where('portal_token', $token)
            ->firstOrFail();
    }
}
