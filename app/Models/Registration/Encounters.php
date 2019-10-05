<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Http\Controllers\Master\IdController;
use Carbon\Carbon;

class Encounters extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'encounterId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

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

    public function Transactions() {
      return $this->hasMany('App\Models\Patient\PatientsTransactions','hn','hn')->where('encounterId',$this->encounterId);
    }

    public function fromAppointment() {
        return $this->hasOne('App\Models\Appointment\Appointments','id','fromAppointmentId')->without(['fromEncounter','toEncounter']);
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
        });

        static::created(function($model) {
            if ($model->Clinic != null) {
                if (count($model->Clinic->autoCharge)>0) {
                    \App\Http\Controllers\Encounter\TransactionController::addTransactions($model->hn,$model->encounterId,$model->Clinic->autoCharge);
                }
            }
        });

        static::saving(function($model) {
            $original = $model->getOriginal();
            if ($model->status != $original['status'] || $model->currectLocation != $original['currentLocation']) {
                $tempStatusLog =  array_wrap($model->statusLog);
                array_push($tempStatusLog,[
                    "status"=>$model->status,
                    "location"=>$model->currectLocation,
                    "statusDateTime"=>Carbon::now()->toIso8601String()
                ]);
                $model->statusLog = $tempStatusLog;
            }

            if ($model->clinicCode != $original['clinicCode'] || $model->doctorCode != $original['doctorCode'] || $model->locationCode != $original['locationCode'] || $model->locationSubunitCode != $original['locationSubunitCode']) {
                $tempLocationLog = array_wrap($model->locationLog); 
                array_push($tempLocationLog,["clinicCode"=>$model->clinicCode,
                "doctorCode"=>$model->doctorCode,
                "locationCode"=>$model->locationCode,
                "locationSubunitCode"=>$model->locationSubunitCode,
                "locationDateTime"=>Carbon::now()->toIso8601String()]);
                $model->locationLog = $tempLocationLog;
            }
        });

        parent::boot();
    }

    protected $casts = [
        'locationLog' => 'array',
        'screening' => 'array',
        'diagnosis' => 'array',
        'summary' => 'array',
        'statusLog' => 'array',
    ];

    protected $with = ['Patient','Location','Clinic','Doctor','fromAppointment'];
}
