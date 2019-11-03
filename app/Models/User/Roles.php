<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Roles extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'roleId';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
      'permissions' => 'array',
    ];
}
