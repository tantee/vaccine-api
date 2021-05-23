<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;

class Permissions extends Model
{
    //
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'permissionId';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
}
