<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    //
    protected $fillable = [
        'name','username', 'email', 'doctorCode','roles','permissions'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
      'roles' => 'array',
      'permissions' => 'array',
    ];
}
