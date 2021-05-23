<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;

class PatientsVitalsigns extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    protected $casts = [
        "temperature" => "float",
        "oxygenSaturation" => "float",
        "height" => "float",
        "weight" => "float",
    ];
}
