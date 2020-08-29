<?php

namespace App\Models\Stock;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class StocksProducts extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function scopeActive($query) {
        return $query->where('quantity','>',0);
    }

    public function scopeNonZero($query) {
        return $query->where('quantity','<>',0);
    }

    public function Product() {
        return $this->hasOne('App\Models\Master\Products','productCode','productCode')->withTrashed();
    }

    public function Encounter() {
        return $this->hasOne('App\Models\Registration\Encounters','encounterId','encounterId')->without(['fromAppointment']);
    }

    protected $dates = [
        'expiryDate',
    ];

    protected $with = ['Product'];
}
