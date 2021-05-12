<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use Carbon\Carbon;

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

    public function Allergies() {
      return $this->hasMany('App\Models\Patient\PatientsAllergies','hn','hn');
    }


    public function getNameThAttribute() {
      $name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','TH')->orWhere('nameType','ALIAS_TH')->orderBy('nameType')->orderBy('id','desc')->first();
      return ($name==null) ? $this->name_en : $name;
    }

    public function getNameEnAttribute() {
      $name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','EN')->orWhere('nameType','ALIAS_EN')->orderBy('nameType')->orderBy('id','desc')->first();
      if ($name==null) $name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','TH')->orWhere('nameType','ALIAS_TH')->orderBy('nameType')->orderBy('id','desc')->first();
      if ($name==null) {
        if ($this->sex==1) {
          $name = new \App\Models\Patient\PatientsNames();
          $name->hn = $this->hn;
          $name->nameType = 'EN';
          $name->namePrefix = '003'
          $name->firstName = 'John';
          $name->lastName = 'Doe';
        } else {
          $name = new \App\Models\Patient\PatientsNames();
          $name->hn = $this->hn;
          $name->nameType = 'EN';
          $name->namePrefix = '004'
          $name->firstName = 'Jane';
          $name->lastName = 'Doe';
        }
      }
      return $name;
    }

    public function getNameRealThAttribute() {
      $name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','TH')->orderBy('id','desc')->first();
      return ($name==null) ? $this->name_real_en : $name;
    }

    public function getNameRealEnAttribute() {
      $name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','EN')->orderBy('id','desc')->first();
      if ($name==null) $name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->where('nameType','TH')->orderBy('id','desc')->first();
      if ($name==null) {
        if ($this->sex==1) {
          $name = new \App\Models\Patient\PatientsNames();
          $name->hn = $this->hn;
          $name->nameType = 'EN';
          $name->namePrefix = '003'
          $name->firstName = 'John';
          $name->lastName = 'Doe';
        } else {
          $name = new \App\Models\Patient\PatientsNames();
          $name->hn = $this->hn;
          $name->nameType = 'EN';
          $name->namePrefix = '004'
          $name->firstName = 'Jane';
          $name->lastName = 'Doe';
        }
      }
      return $name;
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

    protected $appends = ['name_th','name_en','name_real_th','name_real_en','age'];

    protected $hidden = ['personIdDetail'];
}
