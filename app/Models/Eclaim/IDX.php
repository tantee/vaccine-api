<?php

namespace App\Models\Eclaim;

use Illuminate\Database\Eloquent\Model;

class IDX extends Model
{
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'ECLAIM_IDX';
}
