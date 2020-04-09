<?php

namespace App\Models\Eclaim;

use Illuminate\Database\Eloquent\Model;

class ODX extends Model
{
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'ODX';
}
