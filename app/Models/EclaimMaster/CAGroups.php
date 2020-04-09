<?php

namespace App\Models\EclaimMaster;

use Illuminate\Database\Eloquent\Model;

class CAGroups extends Model
{
    protected $connection = 'eclaim';
    protected $table = 'l_cagroup';

    public function scopeActive($query) {
      return $query->where('FLAG','Y')->orderBy('CAGNAME');
    }
}
