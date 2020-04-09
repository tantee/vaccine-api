<?php

namespace App\Models\EclaimMaster;

use Illuminate\Database\Eloquent\Model;

class Hospitals extends Model
{
    protected $connection = 'eclaim';
    protected $table = 'l_hospital';
}
