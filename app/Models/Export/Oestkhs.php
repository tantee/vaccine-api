<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class Oestkhs extends Model
{
    protected $primaryKey = 'DOCNUM';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'OESTKH';
}
