<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class RolesPermissions extends Model
{
    //
    use SoftDeletes,UserStamps;
    
    protected $guarded = [];
}
