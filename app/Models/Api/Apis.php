<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apis extends Model
{
    use SoftDeletes;
    protected $guarded = [];
}
