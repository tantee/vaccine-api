<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class Oeinvhs extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
      'AMOUNT' => 'float',
      'DISCAMT' => 'float',
      'TOTAL' => 'float',
      'VATRAT' => 'float',
      'VATAMT' => 'float',
      'NETAMT' => 'float',
    ];

    protected $connection = 'export';
}
