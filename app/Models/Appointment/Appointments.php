<?php

namespace App\Models\Appointment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Appointments extends Model
{
    use SoftDeletes,UserStamps;
    protected $guarded = [];

    public function Patient() {
        return $this->hasOne('App\Models\Patient\Patients','hn','hn');
    }

    public function Clinic() {
        return $this->hasOne('App\Models\Master\Clinics','clinicCode','clinicCode');
    }

    public function Doctor() {
        return $this->hasOne('App\Models\Master\Doctors','doctorCode','doctorCode');
    }

    public function fromEncounter() {
        return $this->hasOne('App\Models\Registration\Encounters','encounterId','fromEncounterId')->without('fromAppointment');
    }

    public function toEncounter() {
        return $this->hasMany('App\Models\Registration\Encounters','fromAppointmentId','id')->without('fromAppointment');
    }

    protected $casts = [
      'additionalDetail' => 'array',
    ];

    protected $with = ['Clinic','Doctor','fromEncounter','toEncounter'];
}
