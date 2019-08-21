<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use App\Models\Traits\StoreToAsset;

class PatientsInsurances extends Model
{
    //
    use SoftDeletes,UserStamps,StoreToAsset;

    protected $guarded = [];
    protected $toStores = ['documents'];

    public function Insurance() {
        return $this->hasOne('App\Model\Master\Insurances','insuranceCode','insuranceCode');
    }

    protected $dates = [
        'beginDate',
        'endDate'
    ];

    protected $with = ['Insurance'];
}
