<?php

namespace App\Http\Controllers;

use App\Models\QuickBill;
use Illuminate\View\View;

class PortalQuickBillController extends Controller
{
    public function show(string $token): View
    {
        $quickBill = QuickBill::query()
            ->with(['company', 'client', 'payments'])
            ->where('portal_token', $token)
            ->firstOrFail();

        return view('portal.quick-bill', compact('quickBill'));
    }
}
