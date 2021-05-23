<?php

namespace App\Models\Moph;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Whitelists extends Model
{
    use HasFactory;

    protected $casts = [
        'mophTarget' => 'array',
    ];
}
