<?php
namespace App\Console\Commands;
use App\Models\RecurringService;
use App\Services\CustomerMessagingService;
use App\Services\RecurringBillingService;
use Illuminate\Console\Command;
use Throwable;
class GenerateRecurringBills extends Command {
    protected $signature='recurring:generate';
    protected $description='Generate Quick Bills for recurring services that are due';
    public function handle(RecurringBillingService $billing, CustomerMessagingService $messaging): int {
        $due=RecurringService::query()->where('is_active',true)->whereDate('next_bill_on','<=',today())->with(['client','company'])->orderBy('next_bill_on')->get();
        $ok=0;$bad=0;
        foreach($due as $service){
            try{
                $bill=$billing->generate($service);
                if($service->auto_send_email && filled($bill->client->email)) $messaging->sendQuickBillEmail($bill);
                $ok++; $this->info("Generated {$bill->quick_bill_number} for {$service->client->name}.");
            } catch(Throwable $e){ report($e); $bad++; $this->error("Failed recurring service #{$service->id}: {$e->getMessage()}"); }
        }
        $this->info("Recurring billing complete: {$ok} generated, {$bad} failed.");
        return $bad?self::FAILURE:self::SUCCESS;
    }
}
