<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class StocksRequests extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Dispensings() {
        return $this->hasMany('App\Models\Stock\StocksDispensings','stocksRequestId','id');
    }

    protected $dates = [
        'requestDispensingDate',
    ];

    protected $casts = [
        'requestData' => 'array',
        'statusLog' => 'array',
    ];
}
