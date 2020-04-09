<?php

namespace App\Models\Eclaim;

use Illuminate\Database\Eloquent\Model;

class PAT extends Model
{
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'PAT';
}
