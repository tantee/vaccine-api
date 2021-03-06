<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use TaNteE\LaravelModelApi\Traits\UserStamps;
use App\Http\Controllers\Master\IdController;
use Awobaz\Compoships\Compoships;
use Carbon\Carbon;

class Encounters extends Model
{
    //
    use SoftDeletes,UserStamps,Compoships;

    protected $primaryKey = 'encounterId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function scopeActive($query) {
        return $query->whereNull('dischargeDateTime');
    }

    public function Patient() {
        return $this->belongsTo('App\Models\Patient\Patients','hn','hn');
    }

    public function Location() {
        return $this->hasOne('App\Models\Master\Locations','locationCode','locationCode');
    }

    public function Clinic() {
        return $this->hasOne('App\Models\Master\Clinics','clinicCode','clinicCode');
    }

    public function Doctor() {
        return $this->hasOne('App\Models\Master\Doctors','doctorCode','doctorCode');
    }

    public function Insurances() {
        return $this->hasMany('App\Models\Patient\PatientsInsurances','hn','hn')->where(function ($query) {
                    $query->whereNull('clinics')->orWhereJsonLength('clinics',0)
                        ->orWhereJsonContains('clinics',$this->clinicCode);
                });
    }

    public function Transactions() {
        return $this->hasMany('App\Models\Patient\PatientsTransactions',['hn','encounterId'],['hn','encounterId']);
    }

    public function fromAppointment() {
        return $this->hasOne('App\Models\Appointment\Appointments','id','fromAppointmentId')->without(['fromEncounter','toEncounter']);
    }

    public function Vouchers() {
        return $this->belongsToMany('App\Models\Master\Vouchers', 'encounters_vouchers', 'encounterId','voucherId')
            ->whereNull('encounters_vouchers.deleted_at')
            ->as('voucherDetail')
            ->withPivot('voucherNumber')
            ->withTimestamps()
            ->activeAt($this->admitDateTime);
    }

    public function Diagnoses() {
        return $this->hasMany('App\Models\Registration\EncountersDiagnoses','encounterId','encounterId');
    }

    public function Autocharges() {
        return $this->hasMany('App\Models\Registration\EncountersAutocharges','encounterId','encounterId')->active();
    }

    public static function boot() {
        static::creating(function($model) {
            if (!isset($model->encounterId) || empty($model->encounterId)) {
                $prefix = "\\".implode("\\",str_split($model->encounterType));
                if ($model->encounterType == "IMP") {
                    $prefix .= "ym";
                    $model->encounterId = IdController::issueId("Encounter",$prefix,4,'',true);
                } else {
                    $prefix .= "ymd";
                    $model->encounterId = IdController::issueId("Encounter",$prefix,3,'',false);
                }
            }

            $tempStatusLog =  [];
            array_push($tempStatusLog,[
                "status"=>$model->status,
                "location"=>$model->currectLocation,
                "statusDateTime"=>Carbon::now()->toIso8601String()
            ]);
            $model->statusLog = $tempStatusLog;

            $tempLocationLog = []; 
            array_push($tempLocationLog,["clinicCode"=>$model->clinicCode,
            "doctorCode"=>$model->doctorCode,
            "locationCode"=>$model->locationCode,
            "locationSubunitCode"=>$model->locationSubunitCode,
            "locationDateTime"=>Carbon::now()->toIso8601String()]);
            $model->locationLog = $tempLocationLog;
        });

        static::created(function($model) {
            if ($model->Clinic !== null) {
                if (isset($model->Clinic->autoCharge) && !empty($model->Clinic->autoCharge) && count($model->Clinic->autoCharge)>0) {
                    if ($model->encounterType=="IMP") {
                        $autoCharges = $model->Clinic->autoCharge;
                        data_fill($autoCharges,"*.encounterId",$model->encounterId);

                        $autoCharges = array_map(function ($value) {
                            return Arr::only($value,['encounterId','productCode','quantity','repeatHour','roundHour','limitPerEncounter','limitPerDay']);
                        }, $autoCharges);

                        $validationRule = [
                          'encounterId' => 'required',
                          'productCode' => 'required',
                        ];
                        \TaNteE\LaravelModelApi\LaravelModelApi::createModel($autoCharges,\App\Models\Registration\EncountersAutocharges::class,$validationRule);
                    }
                    
                    \App\Http\Controllers\Encounter\TransactionController::addTransactions($model->hn,$model->encounterId,$model->Clinic->autoCharge);
                }
            }
        });

        static::updating(function($model) {
            $original = $model->getOriginal();
            if ($model->status != $original['status'] || $model->currectLocation != $original['currentLocation']) {
                $tempStatusLog =  Arr::wrap($model->statusLog);
                array_push($tempStatusLog,[
                    "status"=>$model->status,
                    "location"=>$model->currectLocation,
                    "statusDateTime"=>Carbon::now()->toIso8601String()
                ]);
                $model->statusLog = $tempStatusLog;
            }

            if ($model->clinicCode != $original['clinicCode'] || $model->doctorCode != $original['doctorCode'] || $model->locationCode != $original['locationCode'] || $model->locationSubunitCode != $original['locationSubunitCode']) {
                $tempLocationLog = Arr::wrap($model->locationLog); 
                array_push($tempLocationLog,["clinicCode"=>$model->clinicCode,
                "doctorCode"=>$model->doctorCode,
                "locationCode"=>$model->locationCode,
                "locationSubunitCode"=>$model->locationSubunitCode,
                "locationDateTime"=>Carbon::now()->toIso8601String()]);
                $model->locationLog = $tempLocationLog;
            }
        });

        static::updated(function($model){
            $original = $model->getOriginal();
            if (($original['dischargeDateTime']==null && $model->dischargeDateTime!==null) || ($original['dischargeDateTime']!=$model->dischargeDateTime)) {
                //if ($model->encounterType == 'IMP') \App\Http\Controllers\Encounter\IPDController::autoRoundDischarge($model->encounterId);

                //Auto dispense and charge when discharge
                //\App\Http\Controllers\Encounter\DispensingController::dispenseEncounterTemporary($model->encounterId);
                \App\Http\Controllers\Encounter\DispensingController::dispenseEncounter($model->encounterId);
                \App\Http\Controllers\Encounter\DispensingController::chargeDispensingAll($model->encounterId);
            }
        });

        parent::boot();
    }

    protected $dates = [
        'admitDateTime',
        'dischargeDateTime'
    ];

    protected $casts = [
        'locationLog' => 'array',
        'screening' => 'array',
        'summary' => 'array',
        'statusLog' => 'array',
    ];

    protected $with = ['Patient','Location','Clinic','Doctor','fromAppointment'];
}
