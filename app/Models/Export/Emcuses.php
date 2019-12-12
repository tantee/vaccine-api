<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class Emcuses extends Model
{
    //
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'EMCUS';
}
