<?php

namespace App\Models\Patient;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Models\Traits\StoreToAsset;
use Carbon\Carbon;

class PatientsInsurances extends Model
{
    //
    use SoftDeletes,UserStamps,StoreToAsset,Rememberable;

    protected $guarded = [];
    protected $toStores = ['documents'];

    public function Payer() {
        return $this->hasOne('App\Models\Master\Payers','payerCode','payerCode')->withTrashed();
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

    public function scopeActiveClinic($query,$clinicCode = null) {
      $query = $query->whereDate('beginDate','<=',Carbon::now())
                ->where(function ($query) {
                  $query->whereDate('endDate','>=',Carbon::now())->orWhereNull('endDate');
                });
      if (!empty($clinicCode)) {
        $query = $query->where(function ($query) use ($clinicCode) {
                    $query->whereNull('clinics')->orWhereJsonLength('clinics',0)->orWhereJsonContains('clinics',$clinicCode);
                 });
      }
      return $query;
    }

    public function scopeInactive() {
      return $query->withTrashed()->whereNotNull('endDate')->whereDate('endDate','<',Carbon::now());
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
      'clinics' => 'array',
    ];

    protected $with = ['payer'];

    protected $appends = ['amount'];
}
