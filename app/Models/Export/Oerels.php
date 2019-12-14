<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class Oerels extends Model
{
    protected $primaryKey = 'DOCNUM';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
      'AMOUNT' => 'float',
    ];

    protected $connection = 'export';
    protected $table = 'OEREL';
}
