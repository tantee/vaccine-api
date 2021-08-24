<?php

namespace App\Models\Moph;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MophVips extends Model
{
    use HasFactory;

    protected $primaryKey = 'cid';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
