<?php

namespace App\Models\Radiology;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Radiology extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];
}
