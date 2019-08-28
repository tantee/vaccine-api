<?php

namespace App\Models\Patient;

use Illuminate\Database\Eloquent\Model;

class PatientsTransactions extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Product() {
        return $this->hasOne('App\Models\Master\Products','productCode','productCode');
    }

    
}
