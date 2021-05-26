<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;
use Carbon\Carbon;

class Patients extends Model
{
    private $_name = null;
    private $_name_th = null;
    private $_name_real_th = null;
    private $_name_en = null;
    private $_name_real_en = null;

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
      if ($this->_name==null) $this->_name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->get();

      $this->_name_th = $this->_name->whereIn('nameType',['TH','ALIAS_TH'])->sortBy([['nameType','asc'],['id','desc']])->first();

      if ($this->_name_th==null) $this->_name_th = $this->_name->whereIn('nameType',['EN','ALIAS_EN'])->sortBy([['nameType','asc'],['id','desc']])->first();
      if ($this->_name_th==null) {
        if ($this->sex==1) {
          $this->_name_th = new \App\Models\Patient\PatientsNames();
          $this->_name_th->hn = $this->hn;
          $this->_name_th->nameType = 'EN';
          $this->_name_th->namePrefix = '003';
          $this->_name_th->firstName = 'John';
          $this->_name_th->lastName = 'Doe';
        } else {
          $this->_name_th = new \App\Models\Patient\PatientsNames();
          $this->_name_th->hn = $this->hn;
          $this->_name_th->nameType = 'EN';
          $this->_name_th->namePrefix = '004';
          $this->_name_th->firstName = 'Jane';
          $this->_name_th->lastName = 'Doe';
        }
      }
      return $this->_name_th;
    }

    public function getNameEnAttribute() {
      if ($this->_name==null) $this->_name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->get();

      $this->_name_en = $this->_name->whereIn('nameType',['EN','ALIAS_EN'])->sortBy([['nameType','asc'],['id','desc']])->first();
      if ($this->_name_en==null) $this->_name_en = $this->_name_th;

      return $this->_name_en;
    }

    public function getNameRealThAttribute() {
      if ($this->_name==null) $this->_name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->get();

      $this->_name_real_th = $this->_name->where('nameType','TH')->sortBy([['nameType','asc'],['id','desc']])->first();

      if ($this->_name_real_th==null) $this->_name_real_th = $this->_name->where('nameType','EN')->sortBy([['nameType','asc'],['id','desc']])->first();
      if ($this->_name_real_th==null) {
        if ($this->sex==1) {
          $this->_name_real_th = new \App\Models\Patient\PatientsNames();
          $this->_name_real_th->hn = $this->hn;
          $this->_name_real_th->nameType = 'EN';
          $this->_name_real_th->namePrefix = '003';
          $this->_name_real_th->firstName = 'John';
          $this->_name_real_th->lastName = 'Doe';
        } else {
          $this->_name_real_th = new \App\Models\Patient\PatientsNames();
          $this->_name_real_th->hn = $this->hn;
          $this->_name_real_th->nameType = 'EN';
          $this->_name_real_th->namePrefix = '004';
          $this->_name_real_th->firstName = 'Jane';
          $this->_name_real_th->lastName = 'Doe';
        }
      }
      return $this->_name_real_th;
    }

    public function getNameRealEnAttribute() {
      if ($this->_name==null) $this->_name = \App\Models\Patient\PatientsNames::where('hn',$this->hn)->get();

      $this->_name_real_en = $this->_name->where('nameType','EN')->sortBy([['nameType','asc'],['id','desc']])->first();
      if ($this->_name_real_en==null) $this->_name_real_en = $this->_name_real_th;

      return $this->_name_real_en;
    }

    public function getAgeAttribute() {
      if ($this->dateOfDeath!==null) $interval = $this->dateOfDeath->diffAsCarbonInterval($this->dateOfBirth);
      else $interval = Carbon::now()->diffAsCarbonInterval($this->dateOfBirth);

      return $interval->locale('th_TH')->forHumans(['parts'=>2]);
    }

    public function setDateOfBirthAttribute($value) {
        $birthDate = \Carbon\Carbon::parse($value)->timezone(config('app.timezone'));
        if ($birthDate->year - \Carbon\Carbon::now()->year > 300) {
          $birthDate->year = $birthDate->year - 543;
        }
        $this->attributes['dateOfBirth'] = $birthDate->format("Y-m-d");
    }

    public function setDateOfDeathAttribute($value) {
        $deathDate = \Carbon\Carbon::parse($value)->timezone(config('app.timezone'));
        if ($deathDate->year - \Carbon\Carbon::now()->year > 300) {
          $deathDate->year = $deathDate->year - 543;
        }
        $this->attributes['dateOfDeath'] = $deathDate->format("Y-m-d");
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

    protected $appends = ['name_th','name_en','age'];

    protected $hidden = ['personIdDetail'];
}
