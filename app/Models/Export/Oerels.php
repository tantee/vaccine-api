<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class Oerels extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
      'AMOUNT' => 'float',
    ];

    protected $connection = 'export';
}
