<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Http\Controllers\Master\IdController;
use Carbon\Carbon;

class AccountingInvoices extends Model
{
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'invoiceId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function scopeUnpaid($query) {
        return $query->whereColumn('amountDue','>','amountPaid')->where('isVoid',false);
    }

    public function scopeRecent($query) {
        return $query->where('updated_at','>',Carbon::now()->subHours(env('INVOICE_RECENT_HOURS', '24')))->orWhereHas('Payments', function ($query) {
            $query->where('updated_at','>',Carbon::now()->subHours(env('INVOICE_RECENT_HOURS', '24')));
        });
    }

    public function Transactions() {
        return $this->hasMany('App\Models\Patient\PatientsTransactions','invoiceId','invoiceId');
    }

    public function Payments() {
        return $this->hasMany('App\Models\Accounting\AccountingPayments','invoiceId','invoiceId');
    }

    public function Document() {
        return $this->hasOne('App\Models\Document\Documents','id','documentId');
    }

    public function Patient() {
        return $this->hasOne('App\Models\Patient\Patients','hn','hn');
    }

    public function Insurance() {
        return $this->hasOne('App\Models\Patient\PatientsInsurances','id','patientsInsurancesId')->withTrashed();
    }

    public function getAmountOutstandingAttribute() {
        return ($this->amountDue - $this->amountPaid >= 0) ? $this->amountDue - $this->amountPaid : 0;
    }

    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['amountOutstanding'] = $this->amount_outstanding;

        return $toArray;
    }

    public static function boot() {
        static::creating(function($model) {
            if (!isset($model->invoiceId) || empty($model->invoiceId)) {
                $model->invoiceId = IdController::issueId('invoice',env('INVOICE_ID_FORMAT', 'ym'),env('INVOICE_ID_DIGIT', 6));
            }
        });

        parent::boot();
    }

    protected $casts = [
        "amount" => "float",
        "amountDue" => "float",
        "amountPaid" => "float",
    ];

    protected $with = ['Payments'];
}
