<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class Icgoods extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
      'SELLPR1' => 'float',
      'SELLPR2' => 'float',
      'SELLPR3' => 'float',
      'SELLPR4' => 'float',
    ];

    protected $connection = 'export';
    protected $table = 'ICGOOD';
}
