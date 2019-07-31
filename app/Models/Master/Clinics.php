<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Clinics extends Model
{
    //
    use SoftDeletes,UserStamps;
    protected $primaryKey = 'clinicCode';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function Timetables() {
      return $this->hasMany('App\Models\Appointment\DoctorsTimetables','clinicCode','clinicCode')->orderBy('dayOfWeek')->orderBy('beginTime');
    }

    protected $casts = [
      'defaultDocument' => 'array',
      'autoCharge' => 'array',
    ];
}
