<?php

namespace App\Models\Eclaim;

use Illuminate\Database\Eloquent\Model;

class IOP extends Model
{
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'IOP';
}
