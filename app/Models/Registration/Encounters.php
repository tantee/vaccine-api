<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\Master\IdController;
use Carbon\Carbon;

class Encounters extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'encounterId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    public static function boot() {
        static::creating(function($model) {
            if (!isset($model->encounterId) || empty($model->encounterId)) {
                $prefix = implode('\\',str_split($model->encounterType));
                if ($model->encounterType == "IMP") {
                    $prefix .= "ym";
                    $model->encounterId = IdController::issueId("Encounter",$prefix,4,'',true);
                } else {
                    $prefix .= "ymd";
                    $model->encounterId = IdController::issueId("Encounter",$prefix,3,'',false);
                }
            }
        });

        static::updating(function($model) {
            $original = $model->getOriginal();
            if ($model->status != $original['status']) {
                array_push($model->statusLog,["status"=>$model->status,"statusDateTime"=>Carbon::now()->toIso8601String()]);
            }

            if ($model->clinicCode != $original['clinicCode'] || $model->doctorCode != $original['doctorCode'] || $model->locationCode != $original['locationCode'] || $model->locationSubunitCode != $original['locationSubunitCode']) {
                array_push($model->locationLog,["clinicCode"=>$model->clinicCode,
                "doctorCode"=>$model->doctorCode,
                "locationCode"=>$model->locationCode,
                "locationSubunitCode"=>$model->locationSubunitCode,
                "locationDateTime"=>Carbon::now()->toIso8601String()]);
            }
        });

        parent::boot();
    }

    protected $casts = [
        'locationLog' => 'array',
        'screening' => 'array',
        'diagnosis' => 'array',
        'summary' => 'array',
        'statusLog' => 'array',
    ];
}
