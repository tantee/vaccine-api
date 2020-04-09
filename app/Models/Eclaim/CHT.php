<?php

namespace App\Models\Eclaim;

use Illuminate\Database\Eloquent\Model;

class CHT extends Model
{
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'CHT';
}
