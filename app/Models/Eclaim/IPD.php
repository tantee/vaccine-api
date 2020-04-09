<?php

namespace App\Models\Eclaim;

use Illuminate\Database\Eloquent\Model;

class IPD extends Model
{
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'ECLAIM_IPD';
}
