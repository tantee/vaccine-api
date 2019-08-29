<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Locations extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'locationCode';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
      'subunits' => 'array',
    ];
}
