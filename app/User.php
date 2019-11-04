<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name','username', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function findForPassport($username) {
        $credentials = [
            'username' => $username,
            'password' => request()->password
        ];
        if (auth()->once($credentials)) {
            return auth()->user();
        }
        return false;
    }

    public function validateForPassportPasswordGrant($password) {
        return true;
    }

    public function getAllPermissionsAttribute() {

        $tmpPermissions = collect($this->permissions)->pluck('permissionId');

        foreach(array_wrap($this->roles) as $role) {
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
