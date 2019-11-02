<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Roles extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Permissions() {
        return $this->hasMany('App\Models\User\RolesPermissions','roleId','roleId');
    }
}
