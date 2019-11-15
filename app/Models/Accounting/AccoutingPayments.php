<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Http\Controllers\Master\IdController;

class AccountingPayments extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Document() {
        return $this->hasOne('App\Models\Document\Documents','id','documentId');
    }

    public function CashierPeriod() {
        return $this->belongsTo('App\Models\Accounting\CashiersPeriods','id','cashiersPeriodsId')->without(['Payments']);
    }

    public function Invoice() {
        return $this->belongsTo('App\Models\Accounting\AccountingInvoices','invoiceId','invoiceId');
    }

    public function getAmountOutstandingAttribute() {
        return ($this->amountDue - $this->amountPaid >= 0) ? $this->amountDue - $this->amountPaid : 0;
    }

    public function getCashierIdAttribute() {
        return $this->CashierPeriod->cashierId;
    }

    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['amountOutstanding'] = $this->amount_outstanding;
        $toArray['cashierId'] = $this->cashier_id;

        return $toArray;
    }

    public static function boot() {
        static::creating(function($model) {
            if (!isset($model->receiptId) || empty($model->receiptId)) {
                $model->receiptId = IdController::issueId('receipt',env('RECEIPT_ID_FORMAT', 'ym'),env('RECEIPT_ID_DIGIT', 6));
            }
        });

        static::saved(function($model) {
            $model->invoice->amountPaid = $model->invoice->payments->where("isVoid",false)->sum('amountPaid');
            $model->invoice->save();
        });

        static::deleted(function($model) {
            $model->invoice->amountPaid = $model->invoice->payments->where("isVoid",false)->sum('amountPaid');
            $model->invoice->save();
        });

        parent::boot();
    }
}
