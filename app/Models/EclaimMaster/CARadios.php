<?php

namespace App\Models\EclaimMaster;

use Illuminate\Database\Eloquent\Model;

class CARadios extends Model
{
    protected $connection = 'eclaim';
    protected $table = 'l_caradio';

    protected $primaryKey = 'CAR_CLAIMCODE';
    public $incrementing = false;
    protected $keyType = 'string';

    public function scopeActive($query) {
      return $query->where('FLAG','Y')->orderBy('CAGNAME');
    }
}
