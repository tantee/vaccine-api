<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class Emcuses extends Model
{
    protected $primaryKey = 'CUSCOD';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'EMCUS';
}
