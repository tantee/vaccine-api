<?php

namespace App\Models\Pharmacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\UserStamps;

class PrescriptionsLabels extends Model
{
    use SoftDeletes,UserStamps;

    protected $guarded = [];

    public function Prescription() {
        return $this->belongsTo('App\Models\Pharmacy\Prescriptions','prescriptionId','id');
    }

    public function Product() {
        return $this->hasOne('App\Models\Master\Products','productCode','productCode')->withTrashed();
    }

    protected $casts = [
      'directions' => 'array',
      'cautions' => 'array',
    ];
}
