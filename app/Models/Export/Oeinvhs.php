<?php

namespace App\Models\Export;

use Illuminate\Database\Eloquent\Model;

class Oeinvhs extends Model
{
    protected $primaryKey = 'DOCNUM';
    public $incrementing = false;
    protected $keyType = 'string';
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
    protected $table = 'OEINVH';
}
