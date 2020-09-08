<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;

class Clients extends Model
{
    protected $primaryKey = 'clientId';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $dates = [
        'lastSeen',
    ];

    protected $casts = [
        'configuration' => 'array',
    ];
}
