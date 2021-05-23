<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;

class PatientsNames extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];
}
