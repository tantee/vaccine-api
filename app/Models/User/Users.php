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

    public function getAllPermissionsAttribute() {

        $tmpPermissions = collect($this->permissions)->pluck('permissionId');

        foreach($this->roles as $role) {
            if (isset($role["roleId"])) {
                $tmpRole = \App\Models\User\Roles::find($role["roleId"]);
                if ($tmpRole != null) {
                    $tmpPermissions = array_merge($tmpPermissions,collect($tmpRole->permissions)->pluck('permissionId'));
                }
            }
        }
        
        return $tmpPermissions;
    }

    protected $casts = [
      'roles' => 'array',
      'permissions' => 'array',
    ];

    protected $appends = ['all_permissions'];
}
