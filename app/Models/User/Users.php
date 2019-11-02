<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    //
    protected $fillable = [
        'name','username', 'email', 'doctorCode',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function Permissions() {
        return $this->hasMany('App\Models\User\UsersPermissions','id','userId');
    }

    public function Roles() {
        return $this->belongsToMany('App\Models\User\Roles', 'users_roles', 'userId', 'roleId');
    }
}
