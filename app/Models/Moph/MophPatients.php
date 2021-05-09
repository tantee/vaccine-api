<?php

namespace App\Models\Moph;

use Illuminate\Database\Eloquent\Model;

class MophPatients extends Model
{
    protected $primaryKey = 'hn';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
