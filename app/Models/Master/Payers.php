<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Payers extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'payerCode';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $guarded = [];
}
