<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class StocksProducts extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];
}
