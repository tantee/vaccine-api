<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Prescriptions extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Patient() {
        return $this->belongsTo('App\Models\Patient\Patients','hn','hn');
    }

    public function Encounter() {
        return $this->belongsTo('App\Models\Registration\Encounters','encounterId','encounterId')->without(['Patient','Doctor']);;
    }

    public function Document() {
      return $this->hasOne('App\Models\Document\Documents','id','documentId')->with('template');
    }

    public function Doctor() {
        return $this->hasOne('App\Models\Master\Doctors','doctorCode','doctorCode');
    }

    public function Labels() {
      return $this->hasMany('App\Models\Pharmacy\PrescriptionsLabels','prescriptionId','id');
    }

    public function Dispensings() {
      return $this->hasMany('App\Models\Pharmacy\PrescriptionsDispensings','prescriptionId','id');
    }

    public static function boot() {
        static::creating(function($model) {
            if ($model->doctorCode == null && $model->encounter!=null) {
              $model->doctorCode = $model->encounter->doctorCode;
            }
        });

        parent::boot();
    }

    protected $dates = [
        'scheduleDate',
    ];

    protected $casts = [
      'statusLog' => 'array',
    ];

    protected $with = ['Patient','Encounter','Doctor'];
}
