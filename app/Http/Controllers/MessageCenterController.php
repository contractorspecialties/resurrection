<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CommunicationLog;
use App\Services\CustomerMessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class MessageCenterController extends Controller
{
    public function index(Request $request): View
    {
        $logs = CommunicationLog::query()
            ->where('company_id', $request->user()->company_id)
            ->with(['client', 'estimate', 'quickBill'])
            ->latest()
            ->paginate(40);

        return view('messages.index', compact('logs'));
    }

    public function create(Request $request): View
    {
        $clients = $request->user()
            ->company
            ->clients()
            ->orderBy('name')
            ->get();

        return view('messages.create', compact('clients'));
    }

    public function store(
        Request $request,
        CustomerMessagingService $messaging
    ): RedirectResponse {
        $data = $request->validate([
            'client_id' => ['required', 'integer'],
            'channel' => ['required', 'in:email,sms'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $client = Client::query()
            ->where('company_id', $request->user()->company_id)
            ->whereNull('archived_at')
            ->findOrFail($data['client_id']);

        try {
            if ($data['channel'] === 'email') {
                $messaging->sendFreeformEmail(
                    company: $request->user()->company,
                    client: $client,
                    subject: filled($data['subject'] ?? null)
                        ? trim($data['subject'])
                        : 'Message from '.$request->user()->company->name,
                    message: trim($data['message']),
                );
            } else {
                $messaging->sendFreeformSms(
                    company: $request->user()->company,
                    client: $client,
                    message: trim($data['message']),
                );
            }

            return redirect()
                ->route('messages.index')
                ->with('status', ucfirst($data['channel'])." sent to {$client->name}.");
        } catch (Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'message' => 'Message failed: '.$e->getMessage(),
                ]);
        }
    }
}
