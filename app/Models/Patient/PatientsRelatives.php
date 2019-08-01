<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class PatientsRelatives extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];
}