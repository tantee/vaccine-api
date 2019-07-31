<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class MasterItems extends Model
{
    use SoftDeletes,UserStamps;
    protected $guarded = [];

    public function Group() {
      return $this->belongTo('App\Models\Master\MasterGroups','groupKey','groupKey');
    }

    protected $casts = [
      'properties' => 'array',
    ];
}
