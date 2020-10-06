<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use Carbon\Carbon;

class ProductsPromotions extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    protected $dates = [
        'beginDateTime',
        'endDateTime'
    ];
}
