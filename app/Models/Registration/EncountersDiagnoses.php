<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class EncountersDiagnoses extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public static function boot() {
        static::created(function($model) {
            $patientDx = \App\Models\Patient\PatientsDiagnoses::firstOrNew(["hn"=>$model->hn,"diagnosisType"=>$model->diagnosisType,"icd10"=>$model->diagnosisType]);
            $patientDx->diagnosisText = $model->diagnosisText;
            $patientDx->occurrence += 1;
            $patientDx->save();
        });

        parent::boot();
    }
}
