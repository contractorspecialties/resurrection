<?php
namespace App\Http\Controllers;
use App\Models\Client;
use App\Models\PriceBookItem;
use App\Models\RecurringService;
use App\Services\CustomerMessagingService;
use App\Services\RecurringBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;
class RecurringServiceController extends Controller {
    public function index(Request $request): View {
        $services=RecurringService::query()->where('company_id',$request->user()->company_id)->with('client')->orderByDesc('is_active')->orderBy('next_bill_on')->paginate(40);
        return view('recurring-services.index',compact('services'));
    }
    public function create(Request $request): View|RedirectResponse {
        $clients=$request->user()->company->clients()->orderBy('name')->get();
        if($clients->isEmpty()) return redirect()->route('customers.create')->with('status','Add the customer first, then set up the recurring service.');
        $priceBookItems=PriceBookItem::query()->where('company_id',$request->user()->company_id)->where('is_active',true)->orderBy('category')->orderBy('name')->get();
        $service=new RecurringService(['frequency'=>'monthly','next_bill_on'=>now()->addMonth()->toDateString(),'is_active'=>true]);
        return view('recurring-services.form',compact('service','clients','priceBookItems'));
    }
    public function store(Request $request): RedirectResponse {
        $service=RecurringService::create(['company_id'=>$request->user()->company_id,...$this->validated($request)]);
        return redirect()->route('recurring-services.edit',$service)->with('status',"{$service->name} recurring service created.");
    }
    public function edit(Request $request, RecurringService $recurringService): View {
        $this->authorizeCompany($request,$recurringService);
        $clients=$request->user()->company->clients()->orderBy('name')->get();
        $priceBookItems=PriceBookItem::query()->where('company_id',$request->user()->company_id)->where('is_active',true)->orderBy('category')->orderBy('name')->get();
        $service=$recurringService;
        return view('recurring-services.form',compact('service','clients','priceBookItems'));
    }
    public function update(Request $request, RecurringService $recurringService): RedirectResponse {
        $this->authorizeCompany($request,$recurringService); $recurringService->update($this->validated($request));
        return back()->with('status','Recurring service updated.');
    }
    public function generate(Request $request, RecurringService $recurringService, RecurringBillingService $billing, CustomerMessagingService $messaging): RedirectResponse {
        $this->authorizeCompany($request,$recurringService);
        try{
            $bill=$billing->generate($recurringService);
            if($recurringService->auto_send_email && filled($bill->client->email)) $messaging->sendQuickBillEmail($bill);
            return redirect()->route('quick-bills.show',$bill)->with('status',"{$bill->quick_bill_number} generated from recurring service.");
        } catch(Throwable $e){ report($e); return back()->withErrors(['recurring'=>'Could not generate recurring bill: '.$e->getMessage()]); }
    }
    public function toggle(Request $request, RecurringService $recurringService): RedirectResponse {
        $this->authorizeCompany($request,$recurringService); $recurringService->update(['is_active'=>!$recurringService->is_active]);
        return back()->with('status',$recurringService->is_active?'Recurring service resumed.':'Recurring service paused.');
    }
    private function validated(Request $request): array {
        $d=$request->validate([
            'client_id'=>['required','integer'],'price_book_item_id'=>['nullable','integer'],'name'=>['required','string','max:180'],'description'=>['nullable','string','max:2000'],
            'amount'=>['required','numeric','gt:0','max:99999999'],'frequency'=>['required',Rule::in(['weekly','biweekly','monthly','quarterly','annually'])],
            'next_bill_on'=>['required','date'],'auto_send_email'=>['nullable','boolean'],'is_active'=>['nullable','boolean'],
        ]);
        Client::query()->where('company_id',$request->user()->company_id)->whereNull('archived_at')->findOrFail($d['client_id']);
        if(filled($d['price_book_item_id']??null)) PriceBookItem::query()->where('company_id',$request->user()->company_id)->findOrFail($d['price_book_item_id']);
        return ['client_id'=>(int)$d['client_id'],'price_book_item_id'=>filled($d['price_book_item_id']??null)?(int)$d['price_book_item_id']:null,
            'name'=>trim($d['name']),'description'=>filled($d['description']??null)?trim($d['description']):null,'amount_cents'=>(int)round(((float)$d['amount'])*100),
            'frequency'=>$d['frequency'],'next_bill_on'=>$d['next_bill_on'],'auto_send_email'=>$request->boolean('auto_send_email'),'is_active'=>$request->boolean('is_active')];
    }
    private function authorizeCompany(Request $request, RecurringService $service): void { abort_unless($service->company_id===$request->user()->company_id,404); }
}
