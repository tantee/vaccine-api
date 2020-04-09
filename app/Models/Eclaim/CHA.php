<?php

namespace App\Models\Eclaim;

use Illuminate\Database\Eloquent\Model;

class CHA extends Model
{
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'ECLAIM_CHA';
}
