<?php

namespace App\Models\Eclaim;

use Illuminate\Database\Eloquent\Model;

class ORF extends Model
{
    protected $guarded = [];

    protected $connection = 'export';
    protected $table = 'ORF';
}
