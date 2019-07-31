<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Models\Traits\StoreToAsset;

class Doctors extends Model
{
    //
    use SoftDeletes,UserStamps,StoreToAsset;
    protected $primaryKey = 'doctorCode';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function Timetables() {
      return $this->hasMany('App\Models\Appointment\DoctorsTimetables','doctorCode','doctorCode');
    }

    protected $toStores = ['photo','signature'];
    protected $casts = [
      'name_th' => 'array',
      'name_en' => 'array',
      'photo' => 'array',
      'signature' => 'array',
    ];
}
