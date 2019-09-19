<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Http\Controllers\Master\IdController;

class AccountingInvoices extends Model
{
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'invoiceId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function Transactions() {
        return $this->hasMany('App\Models\Patient\PatientsTransactions','invoiceId','invoiceId');
    }

    public function Payments() {
        return $this->hasMany('App\Models\Accounting\AccountingPayments','invoiceId','invoiceId');
    }

    public function Document() {
        return $this->hasOne('App\Models\Document\Documents','id','documentId');
    }

    public function getAmountPaidAttribute() {
        return $this->payments->sum('amountPaid');
    }

    public function getAmountOutstandingAttribute() {
        return ($this->amountDue - $this->amount_paid >= 0) ? $this->amountDue - $this->amount_paid : 0;
    }

    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['amountPaid'] = $this->amount_paid;
        $toArray['amountOutstanding'] = $this->amount_standing;

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

    protected $with = ['Payments'];
}
