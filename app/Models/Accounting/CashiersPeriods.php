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

    public function voidPayments() {
        return $this->hasMany('App\Models\Accounting\AccountingPayments','isVoidCashiersPeriodsId','id');
    }

    public function Invoices() {
        return $this->hasMany('App\Models\Accounting\AccountingInvoices','cashiersPeriodsId','id');
    }

    public function voidInvoices() {
        return $this->hasMany('App\Models\Accounting\AccountingInvoices','isVoidCashiersPeriodsId','id');
    }

    public function getPaymentSummaryAttribute() {
        $summary = \App\Models\Accounting\AccountingPayments::where('cashiersPeriodsId',$this->id)->where('isVoid',false)->groupBy('paymentMethod')->map(function ($row,$key){
            return [[
                "paymentMethod" => $key,
                "amountPaid" => $row->sum('amountPaid'),
            ]];
        })->flatten(1)->sortBy("paymentMethod");
        return $summary;
    }

    public function getCurrentCashAttribute() {
        $cash = $this->payment_summary->firstWhere('paymentMethod','cash');
        return $this->initialCash + $cash['amountPaid'];
    }

    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['paymentSummary'] = $this->payment_summary;
        $toArray['currentCash'] = $this->current_cash;

        return $toArray;
    }

    protected $dates = [
        'startDateTime',
        'endDateTime',
    ];

    protected $casts = [
        "initialCash" => "float",
        "finalCash" => "float",
    ];

    protected $with = ['Payments'];
}
