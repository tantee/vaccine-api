<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Patients extends Model
{
    use SoftDeletes,UserStamps;
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
      return $this->hasMany('App\Models\Patient\PatientsInsurances','hn','hn');
    }

    public function Transactions() {
      return $this->hasMany('App\Models\Patient\PatientsTransactions','hn','hn');
    }

    public function Assets() {
      return $this->hasMany('App\Models\Asset\Assets','hn','hn');
    }

    public function getNameThAttribute() {
      return \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','TH')->orWhere('nameType','ALIAS_TH')->orderBy('nameType')->orderBy('id','desc')->first();
    }

    public function getNameEnAttribute() {
      return \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','EN')->orWhere('nameType','ALIAS_EN')->orderBy('nameType')->orderBy('id','desc')->first();
    }

    public function getNameRealThAttribute() {
      return \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','TH')->orderBy('id','desc')->first();
    }

    public function getNameRealEnAttribute() {
      return \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','EN')->orderBy('id','desc')->first();
    }

    public function Photos() {
      return $this->Assets()->where(function ($query) {
        $query->where('assetType','id_photo')->orWhere('assetType','patient_photo');
      })->orderBy('id','desc');
    }

    protected $casts = [
      'personIdDetail' => 'array',
    ];

    protected $appends = ['name_th','name_en','name_real_th','name_real_en'];
}
