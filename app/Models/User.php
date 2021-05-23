<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getRolesAttribute($value) {
        return collect(json_decode($value,true))->pluck("roleId")->unique()->values()->all();
    }

    public function getPermissionsAttribute($value) {

        $tmpPermissions = collect(json_decode($value,true))->pluck('permissionId');

        foreach(Arr::wrap($this->roles) as $role) {
            $tmpRole = \App\Models\User\Roles::find($role);
            if ($tmpRole != null) {
                $tmpPermissions = $tmpPermissions->merge(collect($tmpRole->permissions)->pluck('permissionId'));
            }
        }

        $tmpPermissions = $tmpPermissions->unique()->values()->all();

        return $tmpPermissions;
    }
}
