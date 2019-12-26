<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;
use Carbon\Carbon;

class Vouchers extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function scopeActive($query) {
      return $query->whereDate('expiredDateTime','>=',Carbon::now())->orWhereNull('expiredDateTime');
    }

    public function scopeActiveAt($query,$date) {
      return $query->whereDate('expiredDateTime','>=',$date)->orWhereNull('expiredDateTime');
    }

    protected $casts = [
      'conditions' => 'array',
    ];
}
