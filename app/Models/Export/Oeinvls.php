<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class Oeinvls extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
      'TRNQTY' => 'float',
      'UNITPR' => 'float',
      'DISCAMT' => 'float',
      'TRNVAL' => 'float',
    ];

    protected $connection = 'export';
    protected $table = 'OEINVL';
}
