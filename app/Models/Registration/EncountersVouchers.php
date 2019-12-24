<?php

namespace App\Models\Registration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class EncountersVouchers extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];
}
