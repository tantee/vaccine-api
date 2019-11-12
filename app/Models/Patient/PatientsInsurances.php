<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Models\Traits\StoreToAsset;
use Carbon\Carbon;

class PatientsInsurances extends Model
{
    //
    use SoftDeletes,UserStamps,StoreToAsset;

    protected $guarded = [];
    protected $toStores = ['documents'];

    public function Payer() {
        return $this->hasOne('App\Models\Master\Payers','payerCode','payerCode');
    }

    public function Invoices() {
        return $this->hasMany('App\Models\Accounting\AccountingInvoices','patientsInsurancesId','id');
    }

    public function getAmountAttribute() {
        return (float)$this->Invoices()->sum('amount');
    }

    public function scopeActive($query) {
      return $query->whereDate('beginDate','<=',Carbon::now())->where(function ($query) {
        $query->whereDate('endDate','>=',Carbon::now())->orWhereNull('endDate');
      });
    }

    public function scopeActiveAt($query,$date) {
      return $query->whereDate('beginDate','<=',$date)->where(function ($query) use ($date) {
        $query->whereDate('endDate','>=',$date)->orWhereNull('endDate');
      });
    }

    protected $dates = [
        'beginDate',
        'endDate'
    ];

    protected $casts = [
      'limit' => 'float',
      'limitToConfirm' => 'float',
      'limitPerOpd' => 'float',
      'limitPerIpd' => 'float',
      'policies' => 'array',
    ];

    protected $with = ['payer'];

    protected $appends = ['amount'];

    protected $rememberFor = 2;
    protected $rememberCacheTag = 'patientsinsurances_query';
}
