<?php
namespace App\Models;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RecurringService extends Model {
    protected $fillable=['company_id','client_id','price_book_item_id','name','description','amount_cents','frequency','next_bill_on','auto_send_email','is_active','last_generated_at'];
    protected function casts(): array { return ['next_bill_on'=>'date','auto_send_email'=>'boolean','is_active'=>'boolean','last_generated_at'=>'datetime']; }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function priceBookItem(): BelongsTo { return $this->belongsTo(PriceBookItem::class); }
    public function money(): string { return '$'.number_format($this->amount_cents/100,2); }
    public function nextDate(): CarbonImmutable {
        $d=CarbonImmutable::parse($this->next_bill_on);
        return match($this->frequency){'weekly'=>$d->addWeek(),'biweekly'=>$d->addWeeks(2),'monthly'=>$d->addMonthNoOverflow(),'quarterly'=>$d->addMonthsNoOverflow(3),'annually'=>$d->addYearNoOverflow(),default=>throw new \RuntimeException("Unsupported frequency")};
    }
}
