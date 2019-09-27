<?php

namespace App\Models\Accounting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class CashiersPeriods extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function scopeActive($query) {
        return $query->whereNull('endDateTime');
    }

    public function Payments() {
        return $this->hasMany('App\Models\Accounting\AccountingPayments','cashiersPeriodsId','id');
    }

    public function getPaymentSummaryAttribute() {
        return ($this->amountDue - $this->amountPaid >= 0) ? $this->amountDue - $this->amountPaid : 0;
    }

    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['paymentSummary'] = $this->payment_summary;

        return $toArray;
    }

    protected $with = ['Payments'];
}
