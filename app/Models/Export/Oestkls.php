<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class Oestkls extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
      'TRNQTY' => 'float',
    ];

    protected $connection = 'export';
}
