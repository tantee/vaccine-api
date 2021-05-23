<?php

namespace App\Models\Master;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;

class Clinics extends Model
{
    //
    use SoftDeletes,UserStamps,Rememberable;
    protected $primaryKey = 'clinicCode';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public function Timetables() {
      return $this->hasMany('App\Models\Appointment\DoctorsTimetables','clinicCode','clinicCode')->orderBy('dayOfWeek')->orderBy('beginTime');
    }

    public function Location() {
        return $this->hasOne('App\Models\Master\Locations','locationCode','locationCode');
    }

    protected $casts = [
      'defaultDocument' => 'array',
      'autoCharge' => 'array',
    ];

    protected $with = ['Location'];

    public static function boot() {
        static::saved(function($model) {
            $model::flushCache();
        });

        static::deleted(function($model) {
            $model::flushCache();
        });

        static::restored(function($model) {
            $model::flushCache();
        });

        parent::boot();
    }

    protected $rememberFor = 5;
    protected $rememberCacheTag = 'clinics_query';
}
