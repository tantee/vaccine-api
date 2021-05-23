<?php

namespace App\Models\Master;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;
use TaNteE\LaravelModelApi\Traits\StoreToAsset;

class Doctors extends Model
{
    //
    use SoftDeletes,UserStamps,StoreToAsset,Rememberable;
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
    protected $rememberCacheTag = 'doctors_query';
}
