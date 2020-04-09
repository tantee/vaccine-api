<?php

namespace App\Models\Eclaim;

use Illuminate\Database\Eloquent\Model;

class IRF extends Model
{
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'IRF';
}
