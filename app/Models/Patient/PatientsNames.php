<?php

namespace App\Models\Patient;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class PatientsNames extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    protected $rememberFor = 1;
}
