<?php

namespace App\Models\Moph;

use Illuminate\Database\Eloquent\Model;

class MophEncounters extends Model
{
    protected $primaryKey = 'encounterId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
