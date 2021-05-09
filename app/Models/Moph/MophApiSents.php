<?php

namespace App\Models\Moph;

use Illuminate\Database\Eloquent\Model;

class MophApiSents extends Model
{
    protected $guarded = [];

    protected $casts = [
        'requestData' => 'array',
        'responseData' => 'array',
    ];
}
