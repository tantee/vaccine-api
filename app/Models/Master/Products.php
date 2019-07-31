<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class Products extends Model
{
    use SoftDeletes,UserStamps;
    protected $primaryKey = 'productCode';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
      'specification' => 'array',
    ];

    public function scopeActive($query) {
      return $query->where('isActive',true);
    }
}
