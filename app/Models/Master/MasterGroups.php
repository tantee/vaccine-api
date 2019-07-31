<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class MasterGroups extends Model
{
    use SoftDeletes,UserStamps;

    protected $primaryKey = 'groupKey';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];

    public function Items() {
      return $this->hasMany('App\Models\Master\MasterItems','groupKey','groupKey')->orderBy('ordering')->orderBy('itemCode');;
    }

    protected $casts = [
      'defaultProperties' => 'array',
    ];
}
