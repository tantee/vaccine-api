<?php

namespace App\Models\Patient;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use Carbon\Carbon;

class Patients extends Model
{
    use SoftDeletes,UserStamps,Rememberable;
    protected $primaryKey = 'hn';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function Names() {
      return $this->hasMany('App\Models\Patient\PatientsNames','hn','hn');
    }

    public function Addresses() {
      return $this->hasMany('App\Models\Patient\PatientsAddresses','hn','hn');
    }

    public function Relatives() {
      return $this->hasMany('App\Models\Patient\PatientsRelatives','hn','hn');
    }

    public function Insurances() {
      return $this->hasMany('App\Models\Patient\PatientsInsurances','hn','hn')->active()->orderBy('priority');
    }

    public function AllInsurances() {
      return $this->hasMany('App\Models\Patient\PatientsInsurances','hn','hn')->withTrashed()->orderBy('priority');
    }

    public function Transactions() {
      return $this->hasMany('App\Models\Patient\PatientsTransactions','hn','hn');
    }

    public function UninvoicedTransactions() {
      return $this->hasMany('App\Models\Patient\PatientsTransactions','hn','hn')->uninvoiced();
    }

    public function Invoices() {
      return $this->hasMany('App\Models\Accounting\AccountingInvoices','hn','hn')->with('Insurance');
    }

    public function UnpaidInvoices() {
      return $this->hasMany('App\Models\Accounting\AccountingInvoices','hn','hn')->unpaid()->with('Insurance');
    }

    public function Assets() {
      return $this->hasMany('App\Models\Asset\Assets','hn','hn');
    }

    public function Documents() {
      return $this->hasMany('App\Models\Document\Documents','hn','hn');
    }

    public function Encounters() {
      return $this->hasMany('App\Models\Registration\Encounters','hn','hn')->without('Patient');
    }
    
    public function ActiveEncounters() {
      return $this->hasMany('App\Models\Registration\Encounters','hn','hn')->active()->without('Patient');
    }

    public function ActiveEncountersOpd() {
      return $this->hasMany('App\Models\Registration\Encounters','hn','hn')->active()->where('encounterType', 'AMB')->without('Patient');
    }

    public function ActiveEncountersIpd() {
      return $this->hasMany('App\Models\Registration\Encounters','hn','hn')->active()->where('encounterType', 'IMP')->without('Patient');
    }

    public function Diagnoses() {
        return $this->hasMany('App\Models\Patient\PatientsDiagnoses','hn','hn');
    }

    public function Allergies() {
      return $this->hasMany('App\Models\Patient\PatientsAllergies','hn','hn');
    }


    public function getNameThAttribute() {
      $name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','TH')->orWhere('nameType','ALIAS_TH')->orderBy('nameType')->orderBy('id','desc')->first();
      return ($name==null) ? $this->name_en : $name;
    }

    public function getNameEnAttribute() {
      return \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','EN')->orWhere('nameType','ALIAS_EN')->orderBy('nameType')->orderBy('id','desc')->first();
    }

    public function getNameRealThAttribute() {
      $name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','TH')->orderBy('id','desc')->first();
      return ($name==null) ? $this->name_real_en : $name;
    }

    public function getNameRealEnAttribute() {
      return \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','EN')->orderBy('id','desc')->first();
    }

    public function getAgeAttribute() {
      if ($this->dateOfDeath!==null) $interval = $this->dateOfDeath->diffAsCarbonInterval($this->dateOfBirth);
      else $interval = Carbon::now()->diffAsCarbonInterval($this->dateOfBirth);

      return $interval->locale('th_TH')->forHumans(['parts'=>2]);
    }

    public function Photos() {
      return $this->Assets()->where(function ($query) {
        $query->where('assetType','id_photo')->orWhere('assetType','patient_photo');
      })->orderBy('id','desc');
    }

    protected $dates = [
        'dateOfBirth',
        'dateOfDeath'
    ];

    protected $casts = [
      'personIdDetail' => 'array',
    ];

    public static function boot() {
        static::created(function($model) {
            if ($model->personIdType==2) {
              $insuranceData = [
                'hn' => $model->hn,
                'payerType' => '99',
                'isChargeToPatient' => true,
                'beginDate' =>  Carbon::now(),
                'priority' => 99,
                'policies' => [
                  ['priority' => '1', 'insuranceCode' => 'ALL05']
                ],
              ];
              \App\Http\Controllers\DataController::createModel($insuranceData,\App\Models\Patient\PatientsInsurances::class);
            }
        });

        parent::boot();
    }

    protected $appends = ['name_th','name_en','name_real_th','name_real_en','age'];

    protected $hidden = ['personIdDetail'];

    protected $rememberFor = 1;
}
