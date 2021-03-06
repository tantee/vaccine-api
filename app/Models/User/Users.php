<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Users extends Model
{
    //
    protected $fillable = [
        'name','username', 'email','position', 'licenseNo','roles','permissions'
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

        foreach(Arr::wrap($this->roles) as $role) {
            if (isset($role["roleId"])) {
                $tmpRole = \App\Models\User\Roles::find($role["roleId"]);
                if ($tmpRole != null) {
                    $tmpPermissions = $tmpPermissions->merge(collect($tmpRole->permissions)->pluck('permissionId'));
                }
            }
        }
        
        $tmpPermissions = $tmpPermissions->unique()->values()->all();

        return $tmpPermissions;
    }

    protected $casts = [
      'roles' => 'array',
      'permissions' => 'array',
    ];

    protected $appends = ['all_permissions'];
}
