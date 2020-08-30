<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Stocks extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function StocksProducts()
    {
        return $this->hasMany('App\Models\Stock\StocksProducts','stockId','id')->nonZero();
    }
}
