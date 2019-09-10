<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Models\Traits\StoreToAsset;
use Carbon\Carbon;

class PatientsInsurances extends Model
{
    //
    use SoftDeletes,UserStamps,StoreToAsset;

    protected $guarded = [];
    protected $toStores = ['documents'];

    public function Insurance() {
        return $this->hasOne('App\Models\Master\Insurances','insuranceCode','insuranceCode');
    }

    public function scopeActive($query) {
      return $query->whereDate('beginDate','<=',Carbon::now())->where(function ($query) {
        $query->whereDate('endDate','>=',Carbon::now())->orWhereNull('endDate');
      });
    }

    public function scopeActiveAt($query,$date) {
      return $query->whereDate('beginDate','<=',$date)->where(function ($query) use ($date) {
        $query->whereDate('endDate','>=',$date)->orWhereNull('endDate');
      });
    }

    protected $dates = [
        'beginDate',
        'endDate'
    ];

    protected $with = ['Insurance'];
}
