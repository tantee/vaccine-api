<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TaNteE\LaravelModelApi\Traits\UserStamps;

class PatientsTrackers extends Model
{
    use HasFactory,SoftDeletes,UserStamps;

    protected $guarded = [];
}
