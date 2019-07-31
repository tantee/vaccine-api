<?php

namespace App\Models\Appointment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class DoctorsTimetables extends Model
{
    //
    use SoftDeletes,UserStamps;
    protected $guarded = [];
}
