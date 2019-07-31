<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Insurances extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    protected $casts = [
      'coverageInclusion' => 'array',
      'coverageExclusion' => 'array',
    ];
}
