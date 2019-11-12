<?php

namespace App\Models\Master;

use Watson\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Payers extends Model
{
    //
    use SoftDeletes,UserStamps,Rememberable;

    protected $primaryKey = 'payerCode';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $guarded = [];

    protected $rememberFor = 60;
    protected $rememberCacheTag = 'payers_query';
}
