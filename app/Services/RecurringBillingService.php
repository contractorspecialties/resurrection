<?php
namespace App\Services;
use App\Models\Company;
use App\Models\QuickBill;
use App\Models\RecurringService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class RecurringBillingService {
    public function generate(RecurringService $service): QuickBill {
        return DB::transaction(function() use($service){
            $service=RecurringService::query()->lockForUpdate()->with(['company','client'])->findOrFail($service->id);
            if(!$service->is_active) throw new \RuntimeException('This recurring service is paused.');
            $company=Company::query()->lockForUpdate()->findOrFail($service->company_id);
            $bill=$company->quickBills()->create([
                'client_id'=>$service->client_id,
                'quick_bill_number'=>'QB-'.$company->next_quick_bill_number,
                'portal_token'=>Str::random(48),
                'status'=>'payment_due',
                'description'=>$service->name,
                'details'=>$service->description,
                'amount_cents'=>$service->amount_cents,
            ]);
            $company->increment('next_quick_bill_number');
            $service->update(['next_bill_on'=>$service->nextDate()->toDateString(),'last_generated_at'=>now()]);
            return $bill->fresh(['company','client']);
        });
    }
}
