<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use Carbon\Carbon;

class PatientsCodes extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public static function boot() {
        static::created(function($model) {
            if (Carbon::now()->isSameDay($model->issuedDateTime) && $model->codeType == 'nhsoauthcode') {
                \App\Models\Patient\PatientsInsurance::valid()->where('hn',$model->hn)->where('payerType',20)->update(['isTechnicalActive',true]);
            }
        });

        parent::boot();
    }

    protected $dates = [
        'issuedDateTime',
    ];
}
