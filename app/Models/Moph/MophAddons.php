<?php

namespace App\Models\Moph;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;

class MophAddons extends Model
{
    use HasFactory,SoftDeletes,UserStamps;

    protected $guarded = [];

    protected $dates = [
        'appointmentDateTime'
    ];
}
